// RFM12B Transceiver for RaspberryPI.
// to Receiving Data from SenderNodes and Transmit something away
//
// Basiert zum Teil auf der Arbeit von Nathan Chantrell
//
// modified by meigrafd @ 16.12.2013 - for UART on RaspberryPI
// modified by bigdane69 @ 27.01.2015 - for UART read and send 433mhz
//------------------------------------------------------------------------------
#include <RFM12B.h>
#include <SoftwareSerial.h>
//------------------------------------------------------------------------------
// You will need to initialize the radio by telling it what ID it has and what network it's on
// The NodeID takes values from 1-127, 0 is reserved for sending broadcast messages (send to all nodes)
// The Network ID takes values from 0-255
// By default the SPI-SS line used is D10 on Atmega328. You can change it by calling .SetCS(pin) where pin can be {8,9,10}
#define NODEID            22  //network ID used for this unit
#define NETWORKID        210  //the network ID we are on
#define requestACK      true
#define SERIAL_BAUD     9600
//------------------------------------------------------------------------------
// PIN-Konfiguration 
//------------------------------------------------------------------------------
// UART pins
#define rxPin 7 // D7, PA3
#define txPin 3 // D3, PA7. pin der an RXD vom PI geht.
// LED pin
#define LEDpin 8 // D8, PA2 - set to 0 to disable LED
/*
                     +-\/-+
               VCC  1|    |14  GND
          (D0) PB0  2|    |13  AREF (D10)
          (D1) PB1  3|    |12  PA1 (D9)
             RESET  4|    |11  PA2 (D8)
INT0  PWM (D2) PB2  5|    |10  PA3 (D7)
      PWM (D3) PA7  6|    |9   PA4 (D6)
      PWM (D4) PA6  7|    |8   PA5 (D5) PWM
                     +----+
*/

// Initialise UART
SoftwareSerial mySerial(rxPin, txPin);

// Need an instance of the Radio Module
RFM12B radio;

// Store input
String Message, inputString = "";
int SENDTO = 0;

//##############################################################################

static void activityLED (byte state, byte time = 0) {
  if (LEDpin) {
    pinMode(LEDpin, OUTPUT);
    if (time == 0) {
      digitalWrite(LEDpin, state);
    } else {
      digitalWrite(LEDpin, state);
      delay(time);
      digitalWrite(LEDpin, !state);
    }
  }
}

// init Setup
void setup() {
  pinMode(rxPin, INPUT);
  pinMode(txPin, OUTPUT);
  mySerial.begin(SERIAL_BAUD);
  radio.Initialize(NODEID, RF12_433MHZ, NETWORKID);
  if (LEDpin) {
    activityLED(1, 1000); // LED on/off
  }
}

// Loop
void loop() {
  if (radio.ReceiveComplete()) {
    if (radio.CRCPass()) {
      //node ID of TX, extracted from RF datapacket. Not transmitted as part of structure
      mySerial.print(radio.GetSender(), DEC);
      mySerial.print(" ");
      int i;
      for (byte i = 0; i < *radio.DataLen; i++)
        mySerial.print((char)radio.Data[i]);

      if (LEDpin) {
        activityLED(1, 100); // LED on/off
        activityLED(1, 100); // LED on/off
      }
      if (radio.ACKRequested()) {
        radio.SendACK();
        //mySerial.print(" - ACK send");
      }
    } else {
      mySerial.print("BAD-CRC");
      if (LEDpin) {
        activityLED(1, 1000); // LED on/off
      }
    }
    mySerial.println();
  }
  while (mySerial.available() > 0){
    inputString += (char)mySerial.read();
  }
  if (inputString != ""){
    // get SENDTO NodeID
    for (int i = 0; i < inputString.length(); i++) {
      if (inputString.substring(i, i+1) == ':') {
        SENDTO = inputString.substring(0, i).toInt();
        Message = inputString.substring(i+1);
        break;
      }
    }
    char msg[Message.length() + 1];
    Message.toCharArray(msg, Message.length() + 1);
    //activityLED(1); // LED on
    radio.Send(SENDTO, (uint8_t *)msg, strlen(msg), requestACK);
    radio.SendWait(2);
    //activityLED(0); // LED off
    //inputString = "";
    //msg[0] = (char)0;
  }
}
