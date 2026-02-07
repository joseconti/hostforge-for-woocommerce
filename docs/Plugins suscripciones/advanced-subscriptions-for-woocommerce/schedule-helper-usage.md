# Schedule Helper Usage

The file `includes/class-aswc-schedule-helper.php` provides a small utility
class to create scheduled actions for individual subscriptions. The main plugin
loads this file automatically, so you can call its static methods wherever you
need to schedule or cancel events.

## Available methods

- `schedule_single( $subscription_id, $timestamp, $hook, $args = array(), $group = 'aswc' )`
  Schedules a single Action Scheduler event for a subscription.
- `cancel_scheduled( $subscription_id, $hook, $args = array(), $group = 'aswc' )`
  Removes any pending actions matching the subscription and hook.
- `next_scheduled( $subscription_id, $hook, $args = array(), $group = 'aswc' )`
  Returns the timestamp of the next scheduled action if it exists.
- `reschedule_single( $subscription_id, $timestamp, $hook, $args = array(), $group = 'aswc' )`
  Helper to cancel and schedule again in one call.

## Example

```php
$time = strtotime( '+1 day' );
ASWC_Schedule_Helper::schedule_single(
    123,
    $time,
    'aswc_process_subscription_renewal'
);
```

This will run the hook `aswc_process_subscription_renewal` in 24 hours with the
`subscription_id` argument set to `123`.
