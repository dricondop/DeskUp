# DeskUp

DeskUp is a semester project built with Laravel that manages desks and schedules,
including an automated recurring cleaning schedule.


## Running the Cleaning Scheduler

The cleaning logic is executed via a Laravel Artisan command.

### Run once (manual test)
```bash
php artisan cleaning:run                
php artisan events:complete-expired
```
The first command will check whether any cleanings are scheduled every minute

The second command will change any events that has already passed's status to 'completed', and it will do so every 5 minutes

## Run automatically untill you stop it
php artisan schedule:work