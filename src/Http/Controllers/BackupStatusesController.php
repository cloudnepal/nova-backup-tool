<?php

namespace Spatie\BackupTool\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class BackupStatusesController extends ApiController
{
    public function index()
    {
        return Cache::remember('backup-statuses', now()->addSeconds(4), function () {
            return BackupDestinationStatusFactory::createForMonitorConfig($this->getMonitorConfig())
                ->map(function (BackupDestinationStatus $backupDestinationStatus) {
                    return [
                        'name' => $backupDestinationStatus->backupDestination()->backupName(),
                        'disk' => $backupDestinationStatus->backupDestination()->diskName(),
                        'reachable' => $backupDestinationStatus->backupDestination()->isReachable(),
                        'healthy' => $backupDestinationStatus->isHealthy(),
                        'amount' => $backupDestinationStatus->backupDestination()->backups()->count(),
                        'newest' => $backupDestinationStatus->backupDestination()->newestBackup()
                            ? $backupDestinationStatus->backupDestination()->newestBackup()->date()->diffForHumans()
                            : __('No backups present'),
                        'usedStorage' => Format::humanReadableSize($backupDestinationStatus->backupDestination()->usedStorage()),
                    ];
                })
                ->values()
                ->toArray();
        });
    }

    /**
     * Get monitor configuration data.
     * spatie/laravel-backup ^9.x introduce DTO parameter instead of array.
     *
     * @return \Spatie\Backup\Config\MonitoredBackupsConfig|array
     */
    protected function getMonitorConfig()
    {
        $reflection = new \ReflectionMethod(BackupDestinationStatusFactory::class, 'createForMonitorConfig');
        $monitorBackupsType = $reflection->getParameters()[0]->getType()->getName();

        return $monitorBackupsType === 'Spatie\Backup\Config\MonitoredBackupsConfig'
            ? \Spatie\Backup\Config\MonitoredBackupsConfig::fromArray(config('backup.monitor_backups'))
            : config('backup.monitor_backups');
    }
}
