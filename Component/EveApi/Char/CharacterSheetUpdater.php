<?php
namespace Tarioch\EveapiFetcherBundle\Component\EveApi\Char;

use JMS\DiExtraBundle\Annotation as DI;
use Tarioch\EveapiFetcherBundle\Entity\ApiCall;
use Pheal\Pheal;
use Tarioch\EveapiFetcherBundle\Entity\ApiKey;
use Tarioch\EveapiFetcherBundle\Entity\CharCharacterSheet;
use Tarioch\EveapiFetcherBundle\Entity\CharAttributes;
use Tarioch\EveapiFetcherBundle\Entity\CharCorporationRole;
use Tarioch\EveapiFetcherBundle\Entity\CharCorporationRoleAtHq;
use Tarioch\EveapiFetcherBundle\Entity\CharCorporationRoleAtBase;
use Tarioch\EveapiFetcherBundle\Entity\CharCorporationRoleAtOther;
use Tarioch\EveapiFetcherBundle\Entity\CharCorporationTitle;
use Tarioch\EveapiFetcherBundle\Entity\CharSkill;

/**
 * @DI\Service("tarioch.eveapi.char.CharacterSheet")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CharacterSheetUpdater extends AbstractCharUpdater
{
    /**
     * @inheritdoc
     */
    public function update(ApiCall $call, ApiKey $key, Pheal $pheal)
    {
        $charId = $call->getOwner()->getCharacterId();

        $api = $pheal->charScope->CharacterSheet(array('characterID' => $charId));

        $entity = new CharCharacterSheet($charId);
        $entity->setName($api->name);
        $entity->setDateOfBirth(new \DateTime($api->DoB));
        $entity->setRace($api->race);
        $entity->setBloodLine($api->bloodLine);
        $entity->setAncestry($api->ancestry);
        $entity->setGender($api->gender);
        $entity->setCorporationId($api->corporationID);
        $entity->setCorporationName($api->corporationName);
        $entity->setAllianceId($api->allianceID);
        $entity->setAllianceName($api->allianceName);
        $entity->setCloneName($api->cloneName);
        $entity->setCloneSkillPoints($api->cloneSkillPoints);
        $entity->setBalance($api->balance);

        $attributes = new CharAttributes();
        $entity->setAttributes($attributes);
        $attributesApi = $api->attributes;
        $attributes->setIntelligence($attributesApi->intelligence);
        $attributes->setMemory($attributesApi->memory);
        $attributes->setCharisma($attributesApi->charisma);
        $attributes->setPerception($attributesApi->perception);
        $attributes->setWillpower($attributesApi->willpower);

        foreach ($api->skills as $skillApi) {
            $skill = new CharSkill($entity);
            $skill->setTypeId($skillApi->typeID);
            $skill->setLevel($skillApi->level);
            $skill->setSkillpoints($skillApi->skillpoints);
            $skill->setPublished(filter_var($skillApi->published, FILTER_VALIDATE_BOOLEAN));
            $entity->addSkill($skill);
        }

        foreach ($api->corporationRoles as $roleApi) {
            $role = new CharCorporationRole($entity);
            $role->setRoleId($roleApi->roleID);
            $role->setRoleName($roleApi->roleName);
            $entity->addCorporationRole($role);
        }

        foreach ($api->corporationRolesAtHQ as $roleApi) {
            $role = new CharCorporationRoleAtHq($entity);
            $role->setRoleId($roleApi->roleID);
            $role->setRoleName($roleApi->roleName);
            $entity->addCorporationRolesAtHq($role);
        }

        foreach ($api->corporationRolesAtBase as $roleApi) {
            $role = new CharCorporationRoleAtBase($entity);
            $role->setRoleId($roleApi->roleID);
            $role->setRoleName($roleApi->roleName);
            $entity->addCorporationRolesAtBase($role);
        }

        foreach ($api->corporationRolesAtOther as $roleApi) {
            $role = new CharCorporationRoleAtOther($entity);
            $role->setRoleId($roleApi->roleID);
            $role->setRoleName($roleApi->roleName);
            $entity->addCorporationRolesAtOther($role);
        }

        foreach ($api->corporationTitles as $titleApi) {
            $title = new CharCorporationTitle($entity);
            $title->setTitleId($titleApi->titleID);
            $title->setTitleName($titleApi->titleName);
            $entity->addCorporationTitle($title);
        }

        $query = 'delete from TariochEveapiFetcherBundle:CharCharacterSheet c where c.characterId=:characterId';
        $this->entityManager
            ->createQuery($query)
            ->setParameter('characterId', $charId)
            ->execute();

        $this->entityManager->persist($entity);
        return $api->cached_until;
    }
}
