<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\SuperAdmin\Resources\Inquiries\InquiryResource;
use App\Models\ProjectInquiry;
use App\Services\RoleMatrix;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Latest "Start a project" leads, right under the inquiry stats at the
 * top of the dashboard. Rows open the inquiry in Marketing → Inquiries.
 */
class RecentInquiries extends TableWidget
{
    protected static ?string $heading = 'Latest inquiries';

    protected static ?int $sort = -9;

    protected int|string|array $columnSpan = 'full';

    private const STATUS_COLORS = [
        ProjectInquiry::STATUS_NEW => 'warning',
        ProjectInquiry::STATUS_REVIEWED => 'info',
        ProjectInquiry::STATUS_CONTACTED => 'success',
        ProjectInquiry::STATUS_ARCHIVED => 'gray',
    ];

    public static function canView(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_WAITLIST);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => ProjectInquiry::query()
                    ->latest()
                    ->limit(8)
            )
            ->paginated(false)
            // View-only roles (waitlist.view without waitlist.manage) may see
            // the rows but can't open the edit page — don't link them to a 403.
            ->recordUrl(fn (ProjectInquiry $r): ?string => InquiryResource::canEdit($r)
                ? InquiryResource::getUrl('edit', ['record' => $r])
                : null)
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->color('gray')
                    ->url(InquiryResource::getUrl('index')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->weight('bold'),
                TextColumn::make('email')
                    ->copyable(),
                TextColumn::make('project_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => self::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->tooltip(fn (ProjectInquiry $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->emptyStateHeading('No inquiries yet')
            ->emptyStateDescription('Visitors who submit the “Start a project” form will appear here.');
    }
}
