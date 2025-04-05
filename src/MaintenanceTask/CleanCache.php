<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ApiServer\MaintenanceTask;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Plugin\ApiServer\My;
use Dotclear\Plugin\ApiServer\ApiServerCache;
use Dotclear\Plugin\maintenance\MaintenanceTask;
use Exception;

/**
 * @brief       ApiServer module maintenance task to clean cache
 * @ingroup     ApiServer
 *
 * @author      Jean-Chirstian Paul Denis
 * @copyright   AGPL-3.0
 */
class CleanCache extends MaintenanceTask
{
    protected ?string $id = 'ApiServerCache';

    /**
     * Initializes the task.
     */
    protected function init(): void
    {
        $this->tab     = 'maintenance';
        $this->group   = 'purge';
        $this->task    = __('Empty API cache directory');
        $this->success = __('API cache directory emptied.');
        $this->error   = __('Failed to empty API cache directory.');

        $this->description = sprintf(__('API calls create cache files. Notice : with some hosters, the templates cache cannot be emptied with this plugin. You may then have to delete the directory <strong>%s</strong> directly on the server with your FTP software.'), ApiServerCache::getPath());
    }

    /**
     * Execute the task.
     */
    public function execute(): bool
    {
        ApiServerCache::clearCache();

        return true;
    }
}
