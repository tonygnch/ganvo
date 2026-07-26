<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Schemas;

use App\Models\Order;
use App\Services\Money;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * THE MERCHANT'S EDIT VIEW OF AN ORDER.
 *
 * Until now Orders were read-only apart from three actions (mark shipped, edit
 * tracking, refund). A shop owner could not fix a misspelt address, correct a
 * quantity agreed on the phone, cancel an order, or — on the enquiry flow,
 * where an order arrives with no money attached and the whole point is that the
 * merchant prices it — do the one job the flow exists for.
 *
 * WHAT IS DELIBERATELY LOCKED ONCE MONEY HAS MOVED
 *
 * The moment an order is paid, its line items, shipping charge and totals stop
 * being a description of what the customer wants and start being a record of
 * what they were actually charged. Editing them then does not move any money —
 * it just makes the order disagree with the Stripe charge, silently, with no
 * trace of which number was real. So on a paid order those fields go read-only
 * and the refund action stays the way to change what is owed.
 *
 * Everything that is NOT a money fact — status, customer details, address,
 * tracking, notes — stays editable at every stage, because those are the things
 * that legitimately need correcting after payment.
 */
class OrderForm
{
    /** Statuses at which the amounts are a payment record rather than a proposal. */
    public const SETTLED = [
        Order::STATUS_PAID,
        Order::STATUS_SHIPPED,
        Order::STATUS_REFUNDED,
    ];

    public static function isSettled(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        return $order->paid_at !== null
            || in_array($order->status, self::SETTLED, true);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*
                 | MAIN COLUMN + SIDEBAR, rather than one flat list.
                 |
                 | Left to itself Filament pairs sections two-across in the
                 | order they are declared, which put a three-field Customer
                 | card beside a five-field Address, the tall Items list beside
                 | the short Totals, and left a hand's depth of dead space under
                 | every short one. Worse, it made the reading order zig-zag:
                 | status ended up top-RIGHT and the items bottom-LEFT.
                 |
                 | So the substance of the order — what was bought and what it
                 | costs — gets the wide column and is read top to bottom, and
                 | everything that is ABOUT the order rather than in it (its
                 | state, who placed it, where it goes, the private note) sits
                 | in a narrow rail beside it. The grid collapses to a single
                 | stack on a small screen, in that same order.
                 */
                Callout::make(__('admin.orders.callout.settled'))
                    ->description(__('admin.orders.callout.settled_help'))
                    ->info()
                    ->visible(fn ($record): bool => self::isSettled($record))
                    ->columnSpanFull(),

                Grid::make(3)
                    /*
                     | WITHOUT THIS the grid is crushed into a third of the page.
                     | A Filament form is itself a 2-column grid, so an
                     | undeclared child takes ONE of those columns and this
                     | Grid(3) then split that half into thirds — a sidebar so
                     | narrow the status value wrapped to one letter per line.
                     | Spanning the form makes its 3 columns divide the real
                     | width: 2 for the order, 1 for the rail.
                     */
                    ->columnSpanFull()
                    ->schema([
                        // ── what was ordered
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make(__('admin.orders.section.items'))
                                    ->description(fn ($record): string => self::isSettled($record)
                                        ? __('admin.orders.section_help.items_locked')
                                        : __('admin.orders.section_help.items'))
                                    ->schema([
                                        Repeater::make('items')
                                            ->label(__('admin.orders.section.items'))
                                            ->hiddenLabel()
                                            ->relationship()
                                            ->disabled(fn ($record): bool => self::isSettled($record))
                                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                                            ->addActionLabel(__('admin.orders.action.add_item'))
                                            ->defaultItems(0)
                                            ->columns(4)
                                            ->schema([
                                                TextInput::make('product_name')
                                                    ->label(__('admin.orders.field.product'))
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                TextInput::make('variant_label')
                                                    ->label(__('admin.orders.field.variant'))
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                TextInput::make('unit_price_cents')
                                                    ->label(__('admin.orders.field.unit_price'))
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->step('0.01')
                                                    ->prefix(fn ($record) => Money::symbol(
                                                        $record?->currency
                                                            ?? auth()->user()?->tenant?->store?->currency
                                                            ?? 'EUR'
                                                    ))
                                                    // Cents in the column, a normal price in the
                                                    // box. Same dance as the product price field.
                                                    ->formatStateUsing(fn ($state) => $state === null
                                                        ? null
                                                        : number_format($state / 100, 2, '.', ''))
                                                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                                                TextInput::make('quantity')
                                                    ->label(__('admin.orders.field.qty'))
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->default(1),
                                            ])
                                            // subtotal is derived, never typed: a line whose
                                            // subtotal disagrees with price x quantity is a bug
                                            // nobody would spot until the totals were wrong.
                                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::withSubtotal($data))
                                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::withSubtotal($data))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.orders.section.totals'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('shipping_method_label')
                                            ->label(__('admin.orders.field.shipping_method'))
                                            ->maxLength(255)
                                            ->disabled(fn ($record): bool => self::isSettled($record)),
                                        TextInput::make('shipping_cents')
                                            ->label(__('admin.orders.field.shipping_cost'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->step('0.01')
                                            ->disabled(fn ($record): bool => self::isSettled($record))
                                            ->prefix(fn ($record) => Money::symbol($record?->currency ?? 'EUR'))
                                            ->formatStateUsing(fn ($state) => number_format(((int) $state) / 100, 2, '.', ''))
                                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                                        // Shown, never edited: the discount is a snapshot of what
                                        // the customer was actually given, and the total is
                                        // recomputed on save from the lines above.
                                        TextInput::make('discount_amount_cents')
                                            ->label(__('admin.orders.field.discount'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix(fn ($record) => Money::symbol($record?->currency ?? 'EUR'))
                                            ->formatStateUsing(fn ($state) => number_format(((int) $state) / 100, 2, '.', '')),
                                        TextInput::make('total_cents')
                                            ->label(__('admin.orders.field.total'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix(fn ($record) => Money::symbol($record?->currency ?? 'EUR'))
                                            ->helperText(__('admin.orders.help.total'))
                                            ->formatStateUsing(fn ($state) => number_format(((int) $state) / 100, 2, '.', '')),
                                    ]),
                            ]),

                        // ── what is true about it
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make(__('admin.orders.section.state'))
                                    ->columns(1)   // ditto
                                    ->schema([
                                        Select::make('status')
                                            ->label(__('admin.orders.field.status'))
                                            ->options(Order::statusOptions())
                                            ->required()
                                            ->native(false)
                                            ->helperText(__('admin.orders.help.status')),
                                        Textarea::make('notes')
                                            ->label(__('admin.orders.field.notes'))
                                            ->rows(3)
                                            ->maxLength(2000)
                                            ->helperText(__('admin.orders.help.notes'))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.orders.section.customer'))
                                    ->columns(1)   // one per row: this card lives in the narrow rail
                                    ->schema([
                                        TextInput::make('customer_name')
                                            ->label(__('admin.shared.field.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('customer_email')
                                            ->label(__('admin.orders.field.customer_email'))
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('customer_phone')
                                            ->label(__('admin.orders.field.customer_phone'))
                                            ->tel()
                                            ->maxLength(60),
                                    ]),
                                Section::make(__('admin.orders.section.shipping_address'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('shipping_address.line')
                                            ->label(__('admin.orders.field.street'))
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('shipping_address.city')
                                            ->label(__('admin.orders.field.city'))
                                            ->maxLength(120),
                                        TextInput::make('shipping_address.region')
                                            ->label(__('admin.orders.field.region'))
                                            ->maxLength(120),
                                        TextInput::make('shipping_address.postal_code')
                                            ->label(__('admin.orders.field.postal_code'))
                                            ->maxLength(40),
                                        TextInput::make('shipping_address.country')
                                            ->label(__('admin.orders.field.country'))
                                            ->maxLength(120),
                                    ]),
                            ]),
                    ]),

            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withSubtotal(array $data): array
    {
        $data['subtotal_cents'] = (int) round(
            ((int) ($data['unit_price_cents'] ?? 0)) * ((int) ($data['quantity'] ?? 0))
        );

        return $data;
    }
}
