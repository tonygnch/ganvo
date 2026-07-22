<?php

namespace App\Filament\SuperAdmin\Resources\Inquiries\Tables;

use App\Filament\SuperAdmin\Resources\Inquiries\InquiryResource;
use App\Models\ProjectInquiry;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiriesTable
{
    private const STATUS_COLORS = [
        ProjectInquiry::STATUS_NEW => 'warning',
        ProjectInquiry::STATUS_REVIEWED => 'info',
        ProjectInquiry::STATUS_CONTACTED => 'success',
        ProjectInquiry::STATUS_ARCHIVED => 'gray',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Filament's default row link authorizes via the update policy,
            // which allows everyone here (no ProjectInquiryPolicy exists) —
            // gate on the resource's real canEdit() so view-only roles
            // (waitlist.view without waitlist.manage) don't click into a 403.
            ->recordUrl(fn (ProjectInquiry $r) => InquiryResource::canEdit($r)
                ? InquiryResource::getUrl('edit', ['record' => $r])
                : null)
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('project_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextColumn::make('budget')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => self::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('locale')
                    ->label('Lang')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (ProjectInquiry $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(ProjectInquiry::STATUSES, array_map('ucfirst', ProjectInquiry::STATUSES))),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => self::streamCsv(ProjectInquiry::query()->orderBy('created_at', 'desc')->get())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Open')
                    ->visible(fn (ProjectInquiry $r) => InquiryResource::canEdit($r)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markContacted')
                        ->label('Mark as contacted')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => ProjectInquiry::STATUS_CONTACTED]);
                            }
                            Notification::make()->success()->title('Updated ' . $records->count() . ' inquiries')->send();
                        }),
                    BulkAction::make('markArchived')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $row) {
                                $row->update(['status' => ProjectInquiry::STATUS_ARCHIVED]);
                            }
                            Notification::make()->success()->title('Archived ' . $records->count() . ' inquiries')->send();
                        }),
                    BulkAction::make('exportSelected')
                        ->label('Export selected to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::streamCsv($records)),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No inquiries yet')
            ->emptyStateDescription('Visitors who submit the “Start a project” form will appear here.');
    }

    private static function streamCsv(Collection $rows): StreamedResponse
    {
        $filename = 'ganvo-inquiries-' . Carbon::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'email', 'company', 'project_type', 'budget', 'status', 'message', 'locale', 'received_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->name,
                    $row->email,
                    $row->company,
                    $row->project_type,
                    $row->budget,
                    $row->status,
                    $row->message,
                    $row->locale,
                    $row->created_at?->toIso8601String(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
