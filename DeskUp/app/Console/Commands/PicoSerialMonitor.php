<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Desk;

class PicoSerialMonitor extends Command {

    /* This $signature thing is to create the command php artisan pico:monitor, without it and $description it
    will not be able to run the command and the OLED screen in the picoboard will not work */
    protected $signature = 'pico:monitor {port=COM4}';
    protected $description = 'Monitor user sessions and send height data to Pico via USB serial';
    
    //These variables are here to avoid making unnecessary requests if height/user did not change
    private $serialPort = null; // Store the USB port connection
    private $lastUserId = null; // Store the last connected user
    private $lastHeight = null; // Store the last height sent

    public function handle() {
        //Get the specified port in $signature
        $port = $this->argument('port'); 
        $this->info("Trying to connect to port {$port}");

        /*Okay this line is dense, basically this tries to open a connection to the serial port and 
         stores it in $serialPort. More in depth, fopen() is a built-in php function to handle files,
         since windows treats serial ports as special files, you can open them like you would do with
         a regular one, after $port the ':' are to make it look like "COM3:", which is Windows-specific
         syntax for accessing COM ports as file streams. The 'w+b' strig has three parts 'w' opens the
         port for writing/sending data to the pico, '+' adds read capability to be able to recieve data
         back and 'b' is for 'binary mode' which is apparently very important to prevent PHP from line
         endings or doing other text-processing operations that would corrupt the raw serial data being
         sent to the hardware. The @ symbol at the beginning is an error suppression operator, It silences
         any warnings or errors that fopen() might throw if the port doesn't exist or can't be opened, to
         later check it properly avoiding ugly error messages */
        exec("mode {$port}: BAUD=115200 PARITY=N DATA=8 STOP=1");
        $this->serialPort = fopen('\\\\.\\' . $port, 'r+b');
    
            if (!$this->serialPort) {
            $this->error("Couldn't open port: {$port}");
            $this->info("Please verify pico is connected and the port is correct");
            return 1;
        }

        $this->info("Connected to port: {$port}");
        $this->info("Monitoring user session...");

        // Infinte loop checking database
        while (true) {
            $this->checkUserSession();
            usleep(1000000); // 1s
        }
    }

    private function checkUserSession()
    {
        // Look for the last authenticated user (we assume there is an active session if there is a user with an assigned user)
        
        $activeUser = User::whereNotNull('assigned_desk_id')
            ->whereHas('sessions', function($query) {
                $query->where('last_activity', '>', now()->subMinutes(5)->timestamp);
            })
            ->first();

        if ($activeUser) {
            // User logged 
            if ($this->lastUserId !== $activeUser->id) {
                $this->sendMessage("LOGIN:{$activeUser->id}\n");
                $this->lastUserId = $activeUser->id;
                $this->info("→ Connected user: {$activeUser->id}");
            }

            // Get the desk height from the user's assigned desk
            $desk = Desk::where('desk_number', $activeUser->assigned_desk_id)->first();
            if ($desk) {
                $height = $desk->height; // en cm
                if ($this->lastHeight !== $height) {
                    $this->sendMessage("HEIGHT:{$height}\n");
                    $this->lastHeight = $height;
                    $this->info("→ Updated height: {$height} cm");
                }
            }
        } else {
            // No active user
            if ($this->lastUserId !== null) {
                $this->sendMessage("LOGOUT\n");
                $this->info("→ Disconnected user");
                $this->lastUserId = null;
                $this->lastHeight = null;
            }
        }
    }

    private function sendMessage($message)
    {
        if ($this->serialPort) {
            fwrite($this->serialPort, $message);
            fflush($this->serialPort);
        }
    }
}