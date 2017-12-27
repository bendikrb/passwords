<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:47
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\IAppContainer;

/**
 * Class TagObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class TagObjectHelper extends AbstractObjectHelper {

    const LEVEL_PASSWORDS = 'passwords';

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /**
     * TagObjectHelper constructor.
     *
     * @param IAppContainer      $container
     * @param TagService         $tagService
     * @param PasswordService    $passwordService
     * @param TagRevisionService $revisionService
     */
    public function __construct(
        IAppContainer $container,
        TagService $tagService,
        PasswordService $passwordService,
        TagRevisionService $revisionService
    ) {
        parent::__construct($container);

        $this->tagService      = $tagService;
        $this->revisionService = $revisionService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param AbstractModelEntity|Tag $tag
     * @param string                  $level
     *
     * @param bool                    $excludeHidden
     * @param bool                    $excludeTrash
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getApiObject(
        AbstractModelEntity $tag,
        string $level = self::LEVEL_MODEL,
        bool $excludeHidden = true,
        bool $excludeTrash = false
    ): ?array {
        $detailLevel = explode('+', $level);
        /** @var TagRevision $revision */
        $revision = $this->revisionService->findByUuid($tag->getRevision());

        if($excludeTrash && $revision->isTrashed()) return null;
        if($excludeHidden && $revision->isHidden()) return null;

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($tag, $revision);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($tag, $object);
        }

        return $object;
    }

    /**
     * @param Tag         $tag
     * @param TagRevision $revision
     *
     * @return array
     */
    protected function getModel(Tag $tag, TagRevision $revision): array {

        return [
            'id'        => $tag->getUuid(),
            'owner'     => $tag->getUserId(),
            'created'   => $tag->getCreated(),
            'updated'   => $tag->getUpdated(),
            'revision'  => $tag->getRevision(),
            'label'     => $revision->getLabel(),
            'color'     => $revision->getColor(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
        ];
    }

    /**
     * @param Tag   $tag
     * @param array $object
     *
     * @return array
     * @throws \Exception
     */
    protected function getRevisions(Tag $tag, array $object): array {
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($tag->getUuid());

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'owner'     => $revision->getUserId(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'label'     => $revision->getLabel(),
                'color'     => $revision->getColor(),
                'hidden'    => $revision->isHidden(),
                'trashed'   => $revision->isTrashed(),
                'favourite' => $revision->isFavourite()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param TagRevision $revision
     * @param array       $object
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswords(TagRevision $revision, array $object): array {
        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByTag($revision->getModel());

        foreach ($passwords as $password) {
            $obj = $objectHelper->getApiObject($password, self::LEVEL_MODEL, !$revision->isHidden(), !$revision->isTrashed());

            if($obj !== null) $object['passwords'][] = $obj;
        }

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query('PasswordObjectHelper');
        }

        return $this->passwordObjectHelper;
    }
}