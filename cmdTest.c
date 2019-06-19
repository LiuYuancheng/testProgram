#include <stdlib.h>
#include <stdio.h>

int main()
{
  FILE *fp,*outputfile;
  char var[40];

  fp = popen("lsof -c randomTest", "r");
  while (fgets(var, sizeof(var), fp) != NULL) 
    {
      printf("- %s", var);
    }
  pclose(fp);

  outputfile = fopen("text.txt", "a");
  fprintf(outputfile,"%s\n",var);
  fclose(outputfile);

  return 0;
}