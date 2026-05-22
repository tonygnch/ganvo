<?php

namespace App\Filament\SuperAdmin\Resources\MarketingSignups\Tables;

use App\Models\MarketingSignup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingSignupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('locale')
                    ->label('Lang')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                IconColumn::make('notified_at')
                    ->label('Notified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn (MarketingSignup $r) => $r->notified_at !== null),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono'),
                TextColumn::make('created_at')
                    ->label('Signed up')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (MarketingSignup $r) => $r->created_at?->toDayDateTimeString()),
                TextColumn::make('notified_at')
                    ->label('Notified at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->options([
                        'en' => 'English',
                        'bg' => 'Български',
                    ]),
                Filter::make('notified')
                    ->label('Notified')
                    ->query(fn ($query) => $query->whereNotNull('notified_at')),
                Filter::make('not_notified')
                    ->label('Not yet notified')
                    ->query(fn ($query) => $query->whereNull('notified_at')),
            ])
            ->headerActions([
                // CSV export of every row matching the current filters. The
                // returned response is streamed so a 50k-signup waitlist
                // doesn't blow memory.
                \Filament\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        return self::streamCsv(MarketingSignup::query()->orderBy('created_at', 'desc')->get());
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markNotified')
                        ->label('Mark as notified')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Sets notified_at to now. Does not actually send any email — manage the launch send separately.')
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $row) {
                                if ($row->notified_at === null) {
                                    $row->update(['notified_at' => now()]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title("Marked {$count} signups as notified")
                                ->send();
                        }),
                    BulkAction::make('exportSelected')
                        ->label('Export selected to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::streamCsv($records)),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No signups yet')
            ->emptyStateDescription('Visitors who enter their email on the coming-soon page will appear here.');
    }

    /**
     * Stream a CSV of signups. Headers are fixed so the file format stays
     * stable across exports — easy to pipe into Mailchimp / ConvertKit /
     * whatever sends the launch email.
     */
    private static function streamCsv(Collection $rows): StreamedResponse
    {
        $filename = 'ganvo-waitlist-' . Carbon::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'locale', 'ip', 'signed_up_at', 'notified_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->email,
                    $row->locale,
                    $row->ip,
                    $row->created_at?->toIso8601String(),
                    $row->notified_at?->toIso8601String(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
