<?php
declare(strict_types=1);

namespace Xentral\Modules\ApiAccount\Data;

use JsonSerializable;

final class ApiAccountData implements JsonSerializable
{
    /**
     * @param string[] $permissions
     */
    public function __construct(
        private int    $id,
        private string $name,
        private string $initKey,
        private string $importQueueName,
        private string $eventUrl,
        private string $remoteDomain,
        private bool   $active,
        private bool   $importQueueActive,
        private bool   $cleanUtf8Active,
        private int    $transferAccountId,
        private int    $projectId,
        private array  $permissions,
        private bool   $isLegacy,
        private bool   $isHtmlTransformation
    ) {
    }

    /**
     * @param array $formData
     *
     * @return ApiAccountData
     */
    public static function fromFormData(array $formData): ApiAccountData
    {
        return new self(
            $formData['id'], $formData['name'], $formData['init_key'], $formData['import_queue_name'],
            $formData['event_url'], $formData['remotedomain'], $formData['active'],
            $formData['import_queue'], $formData['cleanutf8'], $formData['transfer_account_id'],
            $formData['project_id'], json_decode($formData['permissions']), $formData['is_legacy'],
            $formData['is_html_transformation']
        );
    }

    /**
     * @param array $apiAccountRow
     *
     * @return ApiAccountData
     */
    public static function fromDbState(array $apiAccountRow): ApiAccountData
    {
        return new self(
            $apiAccountRow['id'], $apiAccountRow['bezeichnung'], $apiAccountRow['initkey'],
            $apiAccountRow['importwarteschlange_name'], $apiAccountRow['event_url'],
            $apiAccountRow['remotedomain'], (bool) $apiAccountRow['aktiv'],
            (bool) $apiAccountRow['importwarteschlange'], (bool) $apiAccountRow['cleanutf8'],
            $apiAccountRow['uebertragung_account'], $apiAccountRow['projekt'],
            json_decode($apiAccountRow['permissions']),
            (bool) $apiAccountRow['is_legacy'], (bool) $apiAccountRow['ishtmltransformation']
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getInitKey(): string
    {
        return $this->initKey;
    }

    /**
     * @return string
     */
    public function getImportQueueName(): string
    {
        return $this->importQueueName;
    }

    /**
     * @return string
     */
    public function getEventUrl(): string
    {
        return $this->eventUrl;
    }

    /**
     * @return string
     */
    public function getRemoteDomain(): string
    {
        return $this->remoteDomain;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isImportQueueActive(): bool
    {
        return $this->importQueueActive;
    }

    /**
     * @return bool
     */
    public function isCleanUtf8Active(): bool
    {
        return $this->cleanUtf8Active;
    }

    /**
     * @return int
     */
    public function getTransferAccountId(): int
    {
        return $this->transferAccountId;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return bool
     */
    public function isLegacy(): bool
    {
        return $this->isLegacy;
    }

    public function isHtmlTransformationActive(): bool
    {
        return $this->isHtmlTransformation;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
