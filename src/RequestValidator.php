<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation;

use Nexphant\Validation\Validator;
use Nexphant\Validation\ValidationException;
use Nexphant\Validation\MessageBag;

/**
 * RequestValidator — adds validate() support to ServerRequest-like objects.
 *
 * Usage:
 *   $data = RequestValidator::validate($request->all(), ['email' => Rule::email()->required()]);
 */
class RequestValidator
{
    /**
     * Validate data against rules; throws ValidationException on failure.
     *
     * @param array $data
     * @param array $rules
     * @return array  validated + filtered data
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    /**
     * Validate silently; returns errors or empty MessageBag on success.
     */
    public static function check(array $data, array $rules): MessageBag
    {
        $v = Validator::make($data, $rules);
        $v->fails();
        return $v->errors();
    }
}
