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
            Section::make('Enquiry')
                ->columns(3)
                ->schema([
                    TextEntry::make('subject')
                        ->badge()
                        ->color('info')
                        ->state(fn (StoreMessage $r) => MessageResource::subjectLabel($r->subject)),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => MessageResource::STATUS_COLORS[$state] ?? 'gray'),
                    TextEntry::make('created_at')->label('Received')->dateTime(),
                ]),

            Section::make('Sender')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('phone')->placeholder('—')->copyable(),
                    TextEntry::make('customer.email')
                        ->label('Signed in as')
                        ->placeholder('Guest — no account'),
                ]),

            Section::make('Message')
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
            Section::make('Technical')
                ->columns(3)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('locale')->label('Language')->placeholder('—'),
                    TextEntry::make('ip')->label('IP')->placeholder('—'),
                    TextEntry::make('read_at')->label('First opened')->dateTime()->placeholder('—'),
                    TextEntry::make('user_agent')->label('Browser')->placeholder('—')->columnSpanFull(),
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
                ->label('Reply by email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(fn () => $this->mailtoUrl()),

            Action::make('markReplied')
                ->label('Mark replied')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->record->status !== StoreMessage::STATUS_REPLIED)
                ->action(function () {
                    $this->record->update(['status' => StoreMessage::STATUS_REPLIED]);
                    Notification::make()->success()->title('Marked as replied')->send();
                }),

            Action::make('archive')
                ->label('Archive')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->visible(fn () => $this->record->status !== StoreMessage::STATUS_ARCHIVED)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => StoreMessage::STATUS_ARCHIVED]);
                    Notification::make()->success()->title('Message archived')->send();
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
