#include <time.h>
#include <inttypes.h>



class Timer
{
public:

  void setperiod(unsigned long period);
  void every(unsigned long period, void (*callback)(void),bool start);
   
  void stop();
  void start();
  void update();
  
  unsigned int millis();
    
private:    
  unsigned long period;
  unsigned long lastEventTime;
  void (*callback)(void);
  bool is_working;
  
  uint64_t epochMilli,epochMicro;
 
};

