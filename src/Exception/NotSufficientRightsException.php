<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class NotSufficientRightsException extends AccountStatusException {

}