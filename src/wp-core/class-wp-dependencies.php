<?php
/**
 * Core base class extended to register items.
 *
 * @see _WP_Dependency
 */
class WP_Dependencies {

	/**
	 * An array of all registered dependencies keyed by handle.
	 *
	 * @var array
	 */
	public $registered = [];

	/**
	 * An array of handles of queued dependencies.
	 *
	 * @var array
	 */
	public $queue = [];

	/**
	 * An array of handles of dependencies to queue.
	 *
	 * @var array
	 */
	public $to_do = [];

	/**
	 * An array of handles of dependencies already queued.
	 *
	 * @var array
	 */
	public $done = [];

	/**
	 * Register an item.
	 *
	 * Registers the item if no item of that name already exists.
	 *
	 * @param string           $handle Name of the item. Should be unique.
	 * @param string|bool      $src    Full URL of the item, or path of the item relative
	 *                                 to the WordPress root directory. If source is set to false,
	 *                                 item is an alias of other items it depends on.
	 * @param string[]         $deps   Optional. An array of registered item handles this item depends on.
	 *                                 Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying item version number, if it has one,
	 *                                 which is added to the URL as a query string for cache busting purposes.
	 *                                 If version is set to false, a version number is automatically added
	 *                                 equal to current installed WordPress version.
	 *                                 If set to null, no version is added.
	 * @return bool Whether the item has been registered. True on success, false on failure.
	 */
	public function add(string $handle, string $src, array $deps = [], string $ver = '') {
		if (isset($this->registered[$handle])) {
			return false;
		}

		$extra                     = [];
		$this->registered[$handle] = (object) compact('handle', 'src', 'deps', 'ver', 'extra');
	}

	/**
	 * Un-register an item or items.
	 *
	 * @param string|string[] $handles Item handle (string) or item handles (array of strings).
	 */
	public function remove(string $handles) {
		foreach ((array) $handles as $handle) {
			unset($this->registered[$handle]);
		}
	}

	/**
	 * Add extra item data.
	 *
	 * Adds data to a registered item.
	 *
	 * @param string $handle Name of the item. Should be unique.
	 * @param string $key    The data key.
	 * @param mixed  $value  The data value.
	 * @return bool True on success, false on failure.
	 */
	public function add_data(string $handle, string $key, mixed $value): bool {
		if (!isset($this->registered[$handle])) {
			return false;
		}

		$this->registered[$handle]->extra[$key] = $value;
		return true;
	}

	/**
	 * Get extra item data.
	 *
	 * Gets data associated with a registered item.
	 *
	 * @param string $handle Name of the item. Should be unique.
	 * @param string $key    The data key.
	 * @return mixed Extra item data (string), false otherwise.
	 */
	public function get_data(string $handle, string $key): mixed {
		if (!isset($this->registered[$handle])) {
			return false;
		}

		if (!isset($this->registered[$handle]->extra[$key])) {
			return false;
		}

		return $this->registered[$handle]->extra[$key];
	}

	/**
	 * Queue an item or items.
	 *
	 * Decodes handles and arguments, then queues handles and stores
	 * arguments in the class property $args. For example in extending
	 * classes, $args is appended to the item url as a query string.
	 * Note $args is NOT the $args property of items in the $registered array.
	 *
	 * @param string|string[] $handles Item handle (string) or item handles (array of strings).
	 */
	public function enqueue($handles) {
		foreach ((array) $handles as $handle) {
			if (!in_array($handle, $this->queue, true) && isset($this->registered[$handle])) {
				$this->queue[] = $handle;

				// Reset all dependencies so they must be recalculated in recurse_deps().
				$this->all_queued_deps = null;
			}
		}
	}

	/**
	 * Dequeue an item or items.
	 *
	 * Decodes handles and arguments, then dequeues handles
	 * and removes arguments from the class property $args.
	 *
	 * @param string|string[] $handles Item handle (string) or item handles (array of strings).
	 */
	public function dequeue($handles) {
		foreach ((array) $handles as $handle) {
			$key = array_search($handle, $this->queue, true);

			if (false !== $key) {
				// Reset all dependencies so they must be recalculated in recurse_deps().
				$this->all_queued_deps = null;

				unset($this->queue[$key]);
			}
		}
	}

	/**
	 * Processes the items and dependencies.
	 *
	 * Processes the items passed to it or the queue, and their dependencies.
	 *
	 * @param string|string[]|false $handles Optional. Items to be processed: queue (false),
	 *                                       single item (string), or multiple items (array of strings).
	 *                                       Default false.
	 * @param int|false             $group   Optional. Group level: level (int), no group (false).
	 * @return string[] Array of handles of items that have been processed.
	 */
	public function do_items($handles = false, int $group = 0) {
		/**
		 * If nothing is passed, print the queue. If a string is passed,
		 * print that item. If an array is passed, print those items.
		 */
		$handles = false === $handles ? $this->queue : (array) $handles;
		$this->determines_deps($handles);

		foreach ($this->to_do as $key => $handle) {
			if (!in_array($handle, $this->done, true) && isset($this->registered[$handle])) {
				/**
				 * Attempt to process the item. If successful,
				 * add the handle to the done array.
				 *
				 * Unset the item from the to_do array.
				 */
				if ($this->do_item($handle, $group)) {
					$this->done[] = $handle;
				}

				unset($this->to_do[$key]);
			}
		}

		return $this->done;
	}

	/**
	 * Processes a dependency.
	 *
	 * @param string    $handle Name of the item. Should be unique.
	 * @param int|false $group  Optional. Group level: level (int), no group (false).
	 *                          Default false.
	 * @return bool True on success, false if not set.
	 */
	public function do_item(string $handle, int $group = 0): bool {
		// in footer
		if ($group != $this->get_data($handle, 'group')) {
			return false;
		}

		return isset($this->registered[$handle]);
	}

	/**
	 * Determines dependencies.
	 *
	 * Recursively builds an array of items to process taking
	 * dependencies into account. Does NOT catch infinite loops.
	 *
	 * @param string|string[] $handles   Item handle (string) or item handles (array of strings).
	 * @param bool            $recursion Optional. Internal flag that function is calling itself.
	 *                                   Default false.
	 * @return bool True on success, false on failure.
	 */
	private function determines_deps($handles, bool $recursion = false) {
		$handles = (array) $handles;
		if (!$handles) {
			return false;
		}

		foreach ($handles as $handle) {
			$queued = in_array($handle, $this->to_do, true);

			if (in_array($handle, $this->done, true)) {
				// Already done.
				continue;
			}

			if ($queued) {
				// Already queued and in the right group.
				continue;
			}

			$keep_going = true;
			if (!isset($this->registered[$handle])) {
				$keep_going = false; // Item doesn't exist.
			} elseif ($this->registered[$handle]->deps && array_diff($this->registered[$handle]->deps, array_keys($this->registered))) {
				$keep_going = false; // Item requires dependencies that don't exist.
			} elseif ($this->registered[$handle]->deps && !$this->determines_deps($this->registered[$handle]->deps, true)) {
				$keep_going = false; // Item requires dependencies that don't exist.
			}

			if (!$keep_going) {
				// Either item or its dependencies don't exist.
				if ($recursion) {
					return false; // Abort this branch.
				} else {
					continue; // We're at the top level. Move on to the next one.
				}
			}

			if ($queued) {
				// Already grabbed it and its dependencies.
				continue;
			}

			$this->to_do[] = $handle;
		}

		return true;
	}
}
