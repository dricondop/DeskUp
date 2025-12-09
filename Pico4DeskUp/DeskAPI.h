#ifndef DESKAPI_H
#define DESKAPI_H

#include "pico/stdlib.h"
#include "hardware/uart.h"

class DeskAPI {
private:
    uart_inst_t* uart;
    int currentUserId;
    int currentHeight;
    char receiveBuffer[256];
    int bufferPos;
    
    void parseMessage(const char* message);
    bool readLine();
    
public:
    DeskAPI(uart_inst_t* uart_instance);
    
    void init();
    bool checkForUpdates();
    int getCurrentUserId();
    int getCurrentHeight();
    bool isUserLoggedIn();
};

#endif
