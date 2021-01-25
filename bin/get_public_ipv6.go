package main

import (
	"fmt"
	"net"
)

func main() {
    //[2400:3200::1] 是阿里DNS IPv6 地址
	conn, err := net.Dial("udp", "[2400:3200::1]:53")
	if err != nil {
		fmt.Println("Error", err)
	}

	localed := conn.LocalAddr()

	addr, _ := net.ResolveUDPAddr("udp", localed.String())

	ip := addr.IP

	fmt.Println(ip)
}
