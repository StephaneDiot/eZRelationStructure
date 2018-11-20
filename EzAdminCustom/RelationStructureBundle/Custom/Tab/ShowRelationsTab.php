<?php

namespace EzAdminCustom\RelationStructureBundle\Custom\Tab;

use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use eZ\Publish\API\Repository\Values\Content\Content;
use EzSystems\EzPlatformAdminUi\Tab\ConditionalTabInterface;
use eZ\Publish\API\Repository\ContentService;
use Symfony\Component\Translation\TranslatorInterface;
use EzSystems\EzPlatformAdminUi\UI\Dataset\DatasetFactory;
use Twig\Environment;
use eZ\Publish\API\Repository\ContentTypeService;


class ShowRelationsTab extends AbstractTab implements ConditionalTabInterface
{
    private $webPageContentTypeIdentifiers;

    /** @var \eZ\Publish\API\Repository\ContentService */
    protected $contentService;

    /** @var \EzSystems\EzPlatformAdminUi\UI\Dataset\DatasetFactory */
    protected $datasetFactory;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    protected $contentTypeService;

    protected $contentTypes = array();

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        DatasetFactory $datasetFactory,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        array $webPageContentTypeIdentifiers
    ) {
        parent::__construct($twig, $translator);
        $this->webPageContentTypeIdentifiers = $webPageContentTypeIdentifiers;
        $this->datasetFactory = $datasetFactory;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;

    }

    public function getIdentifier(): string
    {
        return 'custom-tab';
    }

    public function getName(): string
    {
        return /** @Desc("Custom Tab") */
        $this->translator->trans('Arborescence des relations');
    }

    public function renderView(array $parameters): string
    {
        $contentTypes = [];

        /** @var Content $content */
        $content = $parameters['content'];
        $versionInfo = $content->getVersionInfo();

        $relationsDataset = $this->datasetFactory->relations();
        $relationsDataset->load($versionInfo);

        $relations = $relationsDataset->getRelations();

        $result = array();
        $everyRelations = $this->populateRelations($relations, $contentTypes, $result);

        return $this->twig->render('@ezdesign/tab/showRelationsTab.html.twig', [
        'content' => $parameters['content'],
        'contentTypes' => $this->contentTypes,
        'relations' => $everyRelations,
        ]);
    }

    public function populateRelations ($relations)
    {
        $result = array();
        foreach ($relations as $relation) {

            $relation->sourceContentInfo;

            $relationVersionInfoDestination = $this->contentService->loadVersionInfo($relation->getDestinationContentInfo());
            $destinationRelations = $this->contentService->loadRelations($relationVersionInfoDestination);

            $contentTypeId = $relation->getDestinationContentInfo()->contentTypeId;

            if (!isset($this->contentTypes[$contentTypeId])) {
                $this->contentTypes[$contentTypeId] = $this->contentTypeService->loadContentType($contentTypeId);
            }

            if (!empty($destinationRelations)) {

                $result[] = ['name' => $relation->sourceContentInfo->name, 'relation'=> $relation, 'destinationRelationRelations' => $this->populateRelations($destinationRelations)];
            } else {
                $result[] = ['name' => $relation->sourceContentInfo->name, 'relation'=> $relation, 'destinationRelationRelations' => null];
            }
        }
        return $result;
    }


    public function evaluate(array $parameters): bool
    {
        /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType */
        $contentType = $parameters['contentType'];

        foreach ($this->webPageContentTypeIdentifiers as $webPageContentTypeIdentifier) {
            if ($contentType->identifier === $webPageContentTypeIdentifier ) {
                return true;
            }
        }
        return false;
    }
}