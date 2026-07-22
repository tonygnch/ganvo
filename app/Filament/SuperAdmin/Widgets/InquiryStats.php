<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\ProjectInquiry;
use App\Services\RoleMatrix;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Lead-pipeline glance for the studio owner. Sorted above every other
 * dashboard widget (including the account card at -3) because inbound
 * project inquiries are the first thing to check each visit.
 */
class InquiryStats extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    public static function canView(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_WAITLIST);
    }

    protected function getStats(): array
    {
        $new = ProjectInquiry::where('status', ProjectInquiry::STATUS_NEW)->count();
        $thisWeek = ProjectInquiry::where('created_at', '>=', now()->subDays(7))->count();
        $total = ProjectInquiry::count();

        return [
            Stat::make('New inquiries', $new)
                ->description('Awaiting first review')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($new > 0 ? 'warning' : 'success'),

            Stat::make('This week', $thisWeek)
                ->description('Received in the last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('All-time leads', number_format($total))
                ->description('Every inquiry since launch')
                ->color('primary'),
        ];
    }
}
