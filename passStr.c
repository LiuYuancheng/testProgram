#include <stdio.h> 
#include <stdlib.h> 
#include <string.h>
int main()
{
   char myText[12];
   memset(myText, 0x5a, sizeof(myText));
   function(myText, 4);
    
  return 0;
}
 
 
function(char * test, int l)
{	
	printf("<%d>", strlen(test));

	char test2[4];
	
	strncpy(test2,test, l);
	
	printf("<%d>", strlen("Testing1"));

	//test2[strlen(test2)-2]=0;
	printf("<%s>", test2);
   //do stuff
}
