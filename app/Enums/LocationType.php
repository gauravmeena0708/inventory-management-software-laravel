<?php

namespace App\Enums;

enum LocationType: string
{
    case BUILDING = 'BUILDING';
    case FLOOR = 'FLOOR';
    case ZONE = 'ZONE';
    case ROOM = 'ROOM';
    case DATA_HALL = 'DATA_HALL';
    case ROW = 'ROW';
    case RACK = 'RACK';
    case WORKSTATION = 'WORKSTATION';
    case SEAT = 'SEAT';
    case STORE = 'STORE';
    case BIN = 'BIN';
    case NETWORK_POINT = 'NETWORK_POINT';
    case OTHER = 'OTHER';
}
