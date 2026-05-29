<?php

namespace App\Enums;

enum OrganizationType: string
{
    case CSE = 'CSE';
    case Syndicat = 'Syndicat';
    case EntrepriseDirecte = 'Entreprise directe';
    case Association = 'Association';
}
