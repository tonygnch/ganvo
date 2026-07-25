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
                    ->label('From')
                    ->searchable(['name', 'email'])
                    ->weight('bold')
                    ->description(fn (StoreMessage $r) => $r->email),
                TextColumn::make('subject')
                    ->badge()
                    ->color('info')
                    ->state(fn (StoreMessage $r) => MessageResource::subjectLabel($r->subject)),
                TextColumn::make('message')
                    ->label('Message')
                    ->searchable()
                    ->limit(70)
                    ->tooltip(fn (StoreMessage $r) => $r->message),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => MessageResource::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('locale')
                    ->label('Lang')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (StoreMessage $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MessageResource::statusOptions()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Open'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markReplied')
                        ->label('Mark as replied')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => StoreMessage::STATUS_REPLIED]);
                            }
                            Notification::make()->success()->title('Updated ' . $records->count() . ' messages')->send();
                        }),
                    BulkAction::make('markArchived')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => StoreMessage::STATUS_ARCHIVED]);
                            }
                            Notification::make()->success()->title('Archived ' . $records->count() . ' messages')->send();
                        }),
                    // Archiving is the normal disposal path; deletion is here
                    // for spam sweeps, where keeping the row helps nobody.
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription('Enquiries sent from your storefront contact page land here.');
    }
}
