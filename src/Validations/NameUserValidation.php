<?php declare(strict_types=1);
/*
 * This file is part of FlexPHP.
 *
 * (c) Freddie Gar <freddie.gar@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FlexPHP\Database\Validations;

use FlexPHP\Database\Exception\NameUserValidationException;
use FlexPHP\Database\Validators\NameUserValidator;

class NameUserValidation implements ValidationInterface
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function validate(): void
    {
        $validator = new NameUserValidator();

        $violations = $validator->validate($this->name);

        if (0 !== \count($violations)) {
            throw new NameUserValidationException(
                \sprintf("%1\$s:\n%2\$s", $this->name, (string)$violations->get(0)->getMessage())
            );
        }
    }
}
