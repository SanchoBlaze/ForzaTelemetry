<?php
require "ForzaDataParser.php";

//Reduce errors
error_reporting(~E_WARNING);

//Create a UDP socket
if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Couldn't create socket: [$errorcode] $errormsg \n");
}

echo "Socket created \n";

// Bind the source address
if (!socket_bind($sock, "0.0.0.0", 20127)) {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Could not bind socket : [$errorcode] $errormsg \n");
}

echo "Socket bind OK \n";

file_put_contents("forza.csv", ForzaDataParser::csv_header());

//Do some communication, this loop can handle multiple clients
while (1) {
    //echo "Waiting for data ... \n";

    //Receive some data
    $r = socket_recvfrom($sock, $buf, 1024, 0, $remote_ip, $remote_port);
    echo "$remote_ip : $remote_port -- "/* . $buf*/;

    $fdp = new ForzaDataParser($buf, 'fh4');
    file_put_contents("forza.csv", $fdp->to_csv(), FILE_APPEND);
    //print_r($fdp->to_list());


    //Send back the data to the client
//	socket_sendto($sock, "OK " . $buf , 100 , 0 , $remote_ip , $remote_port);
}

socket_close($sock);

