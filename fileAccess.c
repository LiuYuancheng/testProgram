#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>

struct IPtuple{
    char ipAddr[20];
    int port;
};

struct IPtuple getAddr(){
    FILE * fp;
    char * line = NULL;
    size_t len = 0;
    ssize_t read;
    char * pch;

    fp = fopen("configLocal.txt", "r");
    if (fp == NULL){
        printf("file open error");
        exit(EXIT_FAILURE);   
    }
    while ((read = getline(&line, &len, fp)) != -1) {
        printf("Retrieved line of length %zu:\n", read);
        printf("%s", line);
        if (line[0] == '#' || line[0] == '\n' || line == NULL ){
            //remove the comment line
            continue;
        }
        struct IPtuple r = {};
        strcpy(r.ipAddr, strtok(line, ":"));
        printf("-IP:%s\n", r.ipAddr);
        r.port = atoi(strtok(NULL, ":"));
        printf("-Port:%d\n", r.port);
        fclose(fp);
        if (line)
            free(line);
        return r;
    } 
}

int main(void)
{   
    struct IPtuple r = getAddr();
    printf("IP:<%s>\n", r.ipAddr);
    printf("port:<%d>\n", r.port);
    exit(EXIT_SUCCESS);
}
