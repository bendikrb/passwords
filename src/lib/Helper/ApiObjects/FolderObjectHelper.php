<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:45
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\IAppContainer;

/**
 * Class FolderObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class FolderObjectHelper extends AbstractObjectHelper {

    const LEVEL_PARENT    = 'parent';
    const LEVEL_FOLDERS   = 'folders';
    const LEVEL_PASSWORDS = 'passwords';

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var FolderRevisionService
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
     * @var FolderRevision[]
     */
    protected $revisionCache = [];

    /**
     * FolderObjectHelper constructor.
     *
     * @param IAppContainer         $container
     * @param FolderService         $folderService
     * @param PasswordService       $passwordService
     * @param FolderRevisionService $folderRevisionService
     */
    public function __construct(
        IAppContainer $container,
        FolderService $folderService,
        PasswordService $passwordService,
        FolderRevisionService $folderRevisionService
    ) {
        parent::__construct($container);

        $this->folderService   = $folderService;
        $this->passwordService = $passwordService;
        $this->revisionService = $folderRevisionService;
    }

    /**
     * @param AbstractModelEntity|Folder $folder
     * @param string                     $level
     * @param bool                       $excludeHidden
     * @param bool                       $excludeTrash
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getApiObject(
        AbstractModelEntity $folder,
        string $level = self::LEVEL_MODEL,
        bool $excludeHidden = true,
        bool $excludeTrash = false
    ): ?array {
        $detailLevel = explode('+', $level);
        $revision    = $this->revisionService->findByUuid($folder->getRevision());

        if($excludeTrash && $revision->isTrashed()) return null;
        if($excludeHidden && $revision->isHidden()) return null;

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($folder, $revision);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($folder, $object);
        }
        if(in_array(self::LEVEL_PARENT, $detailLevel)) {
            $object = $this->getParent($revision, $object);
        }
        if(in_array(self::LEVEL_FOLDERS, $detailLevel)) {
            $object = $this->getFolders($revision, $object);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object);
        }

        return $object;
    }

    /**
     * @param Folder         $folder
     *
     * @param FolderRevision $revision
     *
     * @return array
     */
    protected function getModel(Folder $folder, FolderRevision $revision): array {

        return [
            'id'        => $folder->getUuid(),
            'owner'     => $folder->getUserId(),
            'created'   => $folder->getCreated(),
            'updated'   => $folder->getUpdated(),
            'revision'  => $revision->getUuid(),
            'label'     => $revision->getLabel(),
            'parent'    => $revision->getParent(),
            'cseType'   => $revision->getCseType(),
            'sseType'   => $revision->getSseType(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
        ];
    }

    /**
     * @param Folder $folder
     * @param array  $object
     *
     * @return array
     * @throws \Exception
     */
    protected function getRevisions(Folder $folder, array $object): array {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($folder->getUuid());

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'owner'     => $revision->getUserId(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'label'     => $revision->getLabel(),
                'parent'    => $revision->getParent(),
                'cseType'   => $revision->getCseType(),
                'sseType'   => $revision->getSseType(),
                'hidden'    => $revision->isHidden(),
                'trashed'   => $revision->isTrashed(),
                'favourite' => $revision->isFavourite()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getParent(FolderRevision $revision, array $object): array {

        $parent = $this->folderService->findByUuid($revision->getParent());
        $obj    = $this->getApiObject($parent, self::LEVEL_MODEL, !$revision->isHidden(), !$revision->isTrashed());

        if($obj !== null) {
            $object['parent'] = $obj;
        } else {
            $folder           = $this->folderService->getBaseFolder();
            $object['parent'] = $this->getApiObject($folder);
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getFolders(FolderRevision $revision, array $object): array {

        $object['folders'] = [];
        $folders           = $this->folderService->findByParent($revision->getModel());

        foreach ($folders as $folder) {
            $obj = $this->getApiObject($folder, self::LEVEL_MODEL, !$revision->isHidden(), !$revision->isTrashed());

            if($obj !== null) $object['folders'][] = $obj;
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswords(FolderRevision $revision, array $object): array {

        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByFolder($revision->getModel());

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