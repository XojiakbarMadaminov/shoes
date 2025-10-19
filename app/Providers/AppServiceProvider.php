<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Remove original product images after the optimized conversion is generated
        Event::listen(ConversionHasBeenCompletedEvent::class, function (ConversionHasBeenCompletedEvent $event) {
            $media = $event->media;

            if ($media->collection_name !== 'images') {
                return;
            }

            if ($event->conversion->getName() !== 'optimized') {
                return;
            }

            if ($media->getCustomProperty('original_deleted')) {
                return;
            }

            $path = $media->getPath();
            if ($path && file_exists($path)) {
                @unlink($path);
            }

            $media->setCustomProperty('original_deleted', true);
            $media->save();
        });
    }
}
