#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>


char gv_ipAddr[20];
int gv_port;
int gv_KeyV; 
int gv_gwID;
int gv_proV;
int gv_cLen; 


void getAddr(){
    FILE * fp;
    char * line = NULL;
    size_t len = 0;
    ssize_t read;
    char * pch;

    fp = fopen("config.txt", "r");
    if (fp == NULL){
        printf("file open error");
        exit(EXIT_FAILURE);   
    }
    while ((read = getline(&line, &len, fp)) != -1) {
        //printf("Retrieved line of length %zu:\n", read);
        //printf("%s\n", line);
        if (line[0] == '#' || line[0] == '\n' || line == NULL ){
            //remove the comment line
            continue;
        }

        char message[20];
        strcpy(message, strtok(line, ":"));

        if(strstr(message, "TCPIP")){
            strcpy(gv_ipAddr, strtok(NULL, ":"));
            gv_ipAddr[strlen(gv_ipAddr)-1] = '\0';
            printf("IP addresss is: %s \n", gv_ipAddr);
        }

        
        if(strstr(message, "PORTN")){
            gv_port = 5005;
            gv_port = atoi(strtok(NULL, ":"));
            printf("port is: %d \n", gv_port);
        }

        
        if(strstr(message, "P_VER")){
            gv_proV = 0; 
            gv_proV = atoi(strtok(NULL, ":"));
            printf("program version is: %d \n", gv_proV);
        }

        
        if(strstr(message, "K_VER")){
            gv_KeyV = 0;
            gv_KeyV = atoi(strtok(NULL, ":"));
            printf("key version is: %d \n", gv_KeyV);
        }

        if(strstr(message, "GW_ID")){
            gv_gwID = 0;
            gv_gwID = atoi(strtok(NULL, ":"));
            printf("Gate way ID is: %d \n", gv_gwID);
        }


        if(strstr(message, "C_LEN")){
            gv_cLen = 0;
            gv_cLen = atoi(strtok(NULL, ":"));
            if (gv_cLen > 16)
                gv_cLen = 16;
            printf("challenge Len : %d \n", gv_cLen);
        }
    } 
        fclose(fp);
    if (line)
        free(line);
}

int main(void)
{   

    getAddr();
    printf("------------------\n");
    printf("IP addresss is: <%s> \n", gv_ipAddr);
    printf("port is: <%d> \n", gv_port);
    printf("program version is: <%d> \n", gv_proV);
    printf("key version is: <%d> \n", gv_KeyV);
    printf("Gate way ID is: <%d> \n", gv_gwID);
    printf("challenge Len : <%d> \n", gv_cLen);

    exit(EXIT_SUCCESS);
}
