#include <stdio.h>
#include <time.h>
/* always assuming int is at least 32 bits */
int rand();
int rseed = 0;
 
inline void srand(int x)
{
	rseed = x;
}

#define RAND_MAX ((1U << 31) - 1)
 
inline int rand()
{
	return rseed = (rseed * 1103515245 + 12345) & RAND_MAX;
}

int main()
{	
	int i;
	printf("rand max is %d\n", RAND_MAX);
    srand(0);
	for (i = 0; i < 100000; i++){
        int randomN = rand()%128000+1;
		printf("%d\n", randomN);
		sleep(1);
    }
	return 0;
}
