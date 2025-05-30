<?php

declare(strict_types=1);

namespace Worksome\VerifyByPhone\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;
use Worksome\VerifyByPhone\Contracts\PhoneVerificationService;
use Worksome\VerifyByPhone\Exceptions\VerificationCodeExpiredException;

final class VerificationCodeIsValid implements ValidationRule, DataAwareRule
{
    /** @var array<mixed> */
    private array $data = [];

    public function __construct(private readonly string|PhoneNumber $phoneNumber)
    {
    }

    /** {@inheritdoc} */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /**
         * @var PhoneVerificationService $service
         *
         * @phpstan-ignore larastanStrictRules.noGlobalLaravelFunction
         */
        $service = app(PhoneVerificationService::class);

        try {
            // @phpstan-ignore argument.type
            throw_unless($service->verify($this->getPhoneNumber(), strval($value)));
        } catch (VerificationCodeExpiredException) {
            $fail('The given verification code has expired. Please request a new one.')->translate();
        } catch (Throwable) {
            $fail('The given verification code is invalid.')->translate();
        }
    }

    private function getPhoneNumber(): PhoneNumber
    {
        if ($this->phoneNumber instanceof PhoneNumber) {
            return $this->phoneNumber;
        }

        if (! array_key_exists($this->phoneNumber, $this->data)) {
            return new PhoneNumber($this->phoneNumber);
        }

        return new PhoneNumber($this->data[$this->phoneNumber]);
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }
}
