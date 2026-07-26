<?php

namespace App\Filament\StoreAdmin\Resources\Messages\Tables;

use App\Filament\StoreAdmin\Resources\Messages\MessageResource;
use App\Models\StoreMessage;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.messages.field.from'))
                    ->searchable(['name', 'email'])
                    ->weight('bold')
                    ->description(fn (StoreMessage $r) => $r->email),
                TextColumn::make('subject')
                    // Explicit label — Filament's fallback derivation from
                    // the column name is English-only.
                    ->label(__('admin.messages.field.subject'))
                    ->badge()
                    ->color('info')
                    ->state(fn (StoreMessage $r) => MessageResource::subjectLabel($r->subject)),
                TextColumn::make('message')
                    ->label(__('admin.messages.field.message'))
                    ->searchable()
                    ->limit(70)
                    ->tooltip(fn (StoreMessage $r) => $r->message),
                TextColumn::make('status')
                    ->label(__('admin.messages.field.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MessageResource::statusLabel($state))
                    ->color(fn (string $state) => MessageResource::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('locale')
                    ->label(__('admin.messages.field.language'))
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.messages.field.received'))
                    ->since()
                    ->sortable()
                    ->tooltip(fn (StoreMessage $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.messages.field.status'))
                    ->options(MessageResource::statusOptions()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.messages.action.open')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markReplied')
                        ->label(__('admin.messages.action.mark_replied'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => StoreMessage::STATUS_REPLIED]);
                            }
                            Notification::make()->success()->title(__('admin.messages.notify.marked_replied_bulk', ['count' => $records->count()]))->send();
                        }),
                    BulkAction::make('markArchived')
                        ->label(__('admin.messages.action.archive'))
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => StoreMessage::STATUS_ARCHIVED]);
                            }
                            Notification::make()->success()->title(__('admin.messages.notify.archived_bulk', ['count' => $records->count()]))->send();
                        }),
                    // Archiving is the normal disposal path; deletion is here
                    // for spam sweeps, where keeping the row helps nobody.
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.messages.empty.heading'))
            ->emptyStateDescription(__('admin.messages.empty.description'));
    }
}
