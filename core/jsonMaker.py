#!/usr/bin/env python
#
# By: Ziad Matni
# Last modified: 06/27/2016

def inputs():
    stuff = ["","","","","","",""]
    stuff[0] = raw_input("Project Name::::::::")
    stuff[1] = raw_input("Initial NC::::::::::")
    stuff[2] = raw_input("Number of Questions:")
    stuff[3] = raw_input("Answers per User::::")
    stuff[4] = raw_input("Timeout in Seconds::")
    stuff[5] = raw_input("Number of users:::::")
 #   stuff[6] = raw_input("Configuration (A, B1, B2, B3):")

    return (stuff)

# MAIN ROUTINE
if __name__ == "__main__":

    param = inputs()
    print "{"
    print "\t \"projectName\": \""+param[0]+"\","
    print "\t \"initialScore\": \""+param[1]+"\","
    print "\t \"numberQuestions\": \""+param[2]+"\","
    print "\t \"answersPerUser\": \""+param[3]+"\","
    print "\t \"timeout\": \""+param[4]+"\","

    print "\t \"users\": ["
    for x in xrange(1,int(param[5])):
        print "\t \t {\"name\": \"user"+str(x)+"\", \"identifier\": \""+str(x)+"\", \"password\": \"pass"+str(x)+"\"},"
    x += 1
    print "\t \t {\"name\": \"user"+str(x)+"\", \"identifier\": \""+str(x)+"\", \"password\": \"pass"+str(x)+"\"}"
    print "\t ],"

    print "\t \"connections\": ["
    print "\t ]"
    print "}"




