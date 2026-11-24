<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * MainMoney Rails: the sellers of a platform and their verification (KYB).
 * A seller's `id` is the value of `X-Merchant-Account`.
 */
final class Sellers extends Resource
{
    /**
     * @param array<string, mixed> $payload sub_type (LEGAL|LOGIC), name, email, country (uuid from countries()),
     *                                      legal_name, phone_number, registration_number, tax_id
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(array $payload): array
    {
        return $this->transport->post('sellers/', $payload);
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(array $query = []): array
    {
        return $this->transport->get('sellers/', $query);
    }

    /**
     * Countries a seller can be created in (send one `id` as `country`).
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function countries(): array
    {
        return $this->transport->get('sellers/countries/');
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get($this->path($id));
    }

    /**
     * @param array<string, mixed> $payload name, legal_name, email, notification_email, phone_number,
     *                                      registration_number, tax_id
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function update(string $id, array $payload): array
    {
        return $this->transport->patch($this->path($id), $payload);
    }

    /**
     * The seller's verification case; follow its `status`.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function kyb(string $id): array
    {
        return $this->transport->get($this->path($id).'kyb/');
    }

    /**
     * Edit the declared data (jurisdiction, ubos, no_ubo_reason) while the case is a DRAFT.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function updateKyb(string $id, array $payload): array
    {
        return $this->transport->patch($this->path($id).'kyb/', $payload);
    }

    /**
     * Open the verification session; the response carries `capture_token`.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function startKyb(string $id): array
    {
        return $this->transport->post($this->path($id).'kyb/start/');
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function submitKyb(string $id): array
    {
        return $this->transport->post($this->path($id).'kyb/submit/');
    }

    /**
     * Create the hosted onboarding link (replaces the previous one).
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function createOnboardingLink(string $id): array
    {
        return $this->transport->post($this->path($id).'onboarding-link/');
    }

    /**
     * Revoke the live onboarding link.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function revokeOnboardingLink(string $id): array
    {
        return $this->transport->delete($this->path($id).'onboarding-link/');
    }

    private function path(string $id): string
    {
        return 'sellers/'.rawurlencode($id).'/';
    }
}
