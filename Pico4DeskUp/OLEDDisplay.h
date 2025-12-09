#ifndef OLEDDISPLAY_H
#define OLEDDISPLAY_H

#include "pico/stdlib.h"
#include "hardware/i2c.h"

class OLEDDisplay {
private:
    i2c_inst_t* i2c;
    uint8_t address;
    
    void sendCommand(uint8_t command);
    void sendData(uint8_t* data, size_t length);
    
public:
    OLEDDisplay(i2c_inst_t* i2c_instance, uint8_t i2c_address);
    
    void init();
    void clear();
    void displayText(const char* text, uint8_t row);
    void displayHeight(int height);
};

#endif
