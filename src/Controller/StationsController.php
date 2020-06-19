<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Form\Type\IndividualType;
use App\Form\Type\ObservationType;
use App\Form\Type\StationType;
use App\Security\Voter\UserVoter;
use App\Service\SlugGenerator;
use App\Service\UploadService;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StationsController.
 */
class StationsController extends PagesController
{
    /* ************************************************ *
     * Stations
     * ************************************************ */

    /**
     * @Route("/participer/stations", name="stations", methods={"GET", "POST"})
     */
    public function stations(Request $request): Response
    {
        $doctrine = $this->getDoctrine();
        $uploadImageService = new UploadService();
        $stationForm = $this->createForm(StationType::class);

        if ($request->isMethod('POST') && !$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        if ($request->isMethod('POST') && 'station' === $request->request->get('action')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }

            $stationForm->handleRequest($request);
            if ($stationForm->isSubmitted() && $stationForm->isValid()) {
                $slugGenerator = new SlugGenerator();
                $station = new Station();
                $createdAt = new DateTime('NOW');

                $stationFormValues = $request->request->get('station');

                $station->setUser($this->getUser());
                $station->setName($stationFormValues['name']);
                $station->setSlug($slugGenerator->slugify($stationFormValues['name']));
                $station->setHabitat($stationFormValues['habitat']);
                $station->setDescription($stationFormValues['description']);
                $station->setIsPrivate(!empty($stationFormValues['is_private']));
                $station->setHeaderImage($uploadImageService->uploadImage($request->files->get('station')['header_image']));
                $station->setLocality($stationFormValues['locality']);
                $station->setLatitude($stationFormValues['latitude']);
                $station->setLongitude($stationFormValues['longitude']);
                $station->setAltitude($stationFormValues['altitude']);
                $station->setInseeCode($stationFormValues['insee_code']);
                $station->setCreatedAt($createdAt);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($station);
                $entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirect' => $request->getUri(),
                    ]);
                }

                return $this->redirect($request->getUri());
            }
        }

        return $this->render('pages/stations.html.twig', [
            'stations' => $doctrine->getRepository(Station::class)->findAll(),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'stationForm' => $stationForm->createView(),
        ]);
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/participer/stations/{slug}", name="stations_show", methods={"GET", "POST"})
     */
    public function stationPage(Request $request, string $slug): Response
    {
        $doctrine = $this->getDoctrine();
        $uploadImageService = new UploadService();
        $individual = new Individual();
        $observation = new Observation();
        $createdAt = new DateTime('NOW');

        $stationRepository = $doctrine->getRepository(Station::class);
        $individualRepository = $doctrine->getRepository(Individual::class);
        $station = $stationRepository->findOneBy(['slug' => $slug]);
        if (!$station) {
            throw new \Exception('Station not found: '.$slug);
        }
        $stationAllIndividuals = $individualRepository->findSpeciesIndividualsForStation($station);

        $individualForm = $this->createForm(IndividualType::class, $individual, ['individuals' => $stationAllIndividuals]);
        $observationForm = $this->createForm(ObservationType::class, $observation, ['individuals' => $stationAllIndividuals]);

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $station->getName(),
        ];
        if ($request->isMethod('POST')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }
            switch ($request->request->get('action')) {
                case 'individual':
                    $individualForm->handleRequest($request);
                    if ($individualForm->isSubmitted() && $individualForm->isValid()) {
                        $individualFormValues = $request->request->get('individual');

                        $individual->setName($individualFormValues['name']);
                        $individual->setUser($this->getUser());
                        $individual->setStation($station);
                        $individual->setSpecies($doctrine->getRepository(Species::class)
                            ->find($individualFormValues['species'])
                        );
                        $individual->setCreatedAt($createdAt);
                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($individual);
                        $entityManager->flush();
                    }
                    break;
                case 'observation':
                    $observationForm->handleRequest($request);
                    if ($observationForm->isSubmitted() && $observationForm->isValid()) {
                        $observationFormValues = $request->request->get('observation');

                        $observation->setUser($this->getUser());
                        $observation->setIndividual(
                            $individualRepository->find($observationFormValues['individual'])
                        );
                        $observation->setEvent(
                            $doctrine->getRepository(Event::class)
                                ->find($observationFormValues['event'])
                        );
                        $observation->setDate(date_create($observationFormValues['date']));
                        $observation->setPicture($uploadImageService->uploadImage($request->files->get('observation')['picture']));
                        $observation->setIsMissing(!empty($observationFormValues['is_missing']));
                        $observation->setDetails($observationFormValues['details']);
                        $observation->setCreatedAt($createdAt);

                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($observation);
                        $entityManager->flush();
                    }
                    break;
                default:
                    break;
            }
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'redirect' => $request->getUri(),
                ]);
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('pages/station-page.html.twig', [
            'station' => $station,
            'individuals' => $stationAllIndividuals,
            'observations' => $doctrine->getRepository(Observation::class)
                ->findAllObservationsInStation($station, $stationAllIndividuals),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'individualForm' => $individualForm->createView(),
            'observationForm' => $observationForm->createView(),
        ]);
    }
}
