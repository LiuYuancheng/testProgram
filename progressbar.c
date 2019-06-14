#include <stdio.h>
#include <time.h>

#define PBSTR "============================================================"
#define PBWIDTH 60

void printProgress (double percentage)
{
    int val = (int) (percentage * 100);
    int lpad = (int) (percentage * PBWIDTH);
    int rpad = PBWIDTH - lpad;

    printf ("\r%3d%% [%.*s%*s]", val, lpad, PBSTR, rpad, "");
    fflush (stdout);
}

   int main (){
       int i = 0;
       for (i = 0; i < 100; i++){
           printProgress((double)i/100);
           usleep(200*1000);
       }
       printf("\n");
   }