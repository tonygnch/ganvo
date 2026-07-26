<?php

namespace App\Filament\StoreAdmin\Resources\Messages\Pages;

use App\Filament\StoreAdmin\Resources\Messages\MessageResource;
use App\Models\StoreMessage;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Opening the enquiry IS the merchant reading it, so flip new → read
        // here — the sidebar badge should only count what nobody has seen.
        $this->record->markRead();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.messages.section.enquiry'))
                ->columns(3)
                ->schema([
                    TextEntry::make('subject')
                        // Explicit label — Filament's fallback derivation
                        // from the attribute name is English-only.
                        ->label(__('admin.messages.field.subject'))
                        ->badge()
                        ->color('info')
                        ->state(fn (StoreMessage $r) => MessageResource::subjectLabel($r->subject)),
                    TextEntry::make('status')
                        ->label(__('admin.messages.field.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state) => MessageResource::statusLabel($state))
                        ->color(fn (string $state) => MessageResource::STATUS_COLORS[$state] ?? 'gray'),
                    TextEntry::make('created_at')->label(__('admin.messages.field.received'))->dateTime(),
                ]),

            Section::make(__('admin.messages.section.sender'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('admin.shared.field.name')),
                    TextEntry::make('email')->label(__('admin.messages.field.email'))->copyable(),
                    TextEntry::make('phone')->label(__('admin.messages.field.phone'))->placeholder('—')->copyable(),
                    TextEntry::make('customer.email')
                        ->label(__('admin.messages.field.signed_in_as'))
                        ->placeholder(__('admin.messages.ph.guest')),
                ]),

            Section::make(__('admin.messages.section.message'))
                ->schema([
                    TextEntry::make('message')
                        ->hiddenLabel()
                        // The visitor's paragraphs matter for readability, and
                        // nl2br over escaped text keeps them without trusting
                        // anything they typed.
                        ->html()
                        ->formatStateUsing(fn (string $state) => nl2br(e($state)))
                        ->columnSpanFull(),
                ]),

            // Only interesting when triaging spam or a broken submission, so
            // it stays folded away.
            Section::make(__('admin.messages.section.technical'))
                ->columns(3)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('locale')->label(__('admin.messages.field.language'))->placeholder('—'),
                    TextEntry::make('ip')->label(__('admin.messages.field.ip'))->placeholder('—'),
                    TextEntry::make('read_at')->label(__('admin.messages.field.first_opened'))->dateTime()->placeholder('—'),
                    TextEntry::make('user_agent')->label(__('admin.messages.field.browser'))->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Replies leave from the merchant's own mailbox — the platform
            // never sends on their behalf, which is why "replied" below is a
            // manual flag rather than something we can detect.
            Action::make('replyByEmail')
                ->label(__('admin.messages.action.reply_by_email'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(fn () => $this->mailtoUrl()),

            Action::make('markReplied')
                ->label(__('admin.messages.action.mark_replied'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->record->status !== StoreMessage::STATUS_REPLIED)
                ->action(function () {
                    $this->record->update(['status' => StoreMessage::STATUS_REPLIED]);
                    Notification::make()->success()->title(__('admin.messages.notify.marked_replied'))->send();
                }),

            Action::make('archive')
                ->label(__('admin.messages.action.archive'))
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->visible(fn () => $this->record->status !== StoreMessage::STATUS_ARCHIVED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => StoreMessage::STATUS_ARCHIVED]);
                    Notification::make()->success()->title(__('admin.messages.notify.archived'))->send();
                }),
        ];
    }

    /**
     * Prefills the merchant's mail client with the sender and what they wrote
     * in about, so replying is one click from the enquiry.
     */
    private function mailtoUrl(): string
    {
        $subject = 'Re: ' . MessageResource::subjectLabel($this->record->subject);

        if ($store = $this->record->tenant?->name) {
            $subject .= ' — ' . $store;
        }

        return 'mailto:' . $this->record->email . '?subject=' . rawurlencode($subject);
    }
}
