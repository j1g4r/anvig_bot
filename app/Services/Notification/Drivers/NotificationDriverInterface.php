<?php

namespace App\Services\Notification\Drivers;

interface NotificationDriverInterface
{
    /**
     * Send a notification.
     *
     * @param string $destination
     * @param string $message
     * @return bool
     */
    public function send(string $destination, string $message): bool;

    /**
     * Get the channel name for this driver.
     *
     * @return string
     */
    public function channel(): string;
}
