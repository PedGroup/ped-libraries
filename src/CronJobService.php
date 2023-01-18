<?php

namespace PedLibraries;

abstract class CronJobService {

	public const CRON_HOOK = '';
	public const RECURRENCE = '';

	public function __construct() {
		$this->scheduleEvent();
		$this->addHook();
	}

	/**
	 * Run cron hook handler
	 * @return mixed
	 */
	public abstract function run();

	/**
	 * Schedule cron event
	 * @return void
	 */
	protected function scheduleEvent() {
		if ( ! wp_next_scheduled( static::CRON_HOOK ) ) {
			wp_schedule_event( time() + 10, static::RECURRENCE, static::CRON_HOOK );
		}
	}

	/**
	 * Add cron hook
	 * @return void
	 */
	protected function addHook() {
		add_action( static::CRON_HOOK, [ $this, 'run' ] );
	}
}
