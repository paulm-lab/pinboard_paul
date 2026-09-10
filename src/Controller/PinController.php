<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Entity\User;
use App\Repository\PinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PinController extends AbstractController
{
    #[Route('/pin', name: 'app_pin_index')]
    public function index(PinRepository $pinRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Afficher uniquement les Pins de l'utilisateur connecté
        $pins = $pinRepository->findBy([
            'user' => $user,
        ]);

        return $this->render('pin/index.html.twig', [
            'pins' => $pins,
        ]);
    }


    #[Route('/pin/create', name: 'app_pin_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $pin = new Pin();

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');

            // Vérification du titre
            if ($title === null || trim((string) $title) === '') {
                $this->addFlash(
                    'danger',
                    'Le titre du Pin est obligatoire.'
                );

                return $this->render('pin/create.html.twig', [
                    'pin' => $pin,
                ]);
            }

            // Minimum 3 caractères
            if (mb_strlen(trim((string) $title)) < 3) {
                $this->addFlash(
                    'danger',
                    'Le titre doit contenir au moins 3 caractères.'
                );

                return $this->render('pin/create.html.twig', [
                    'pin' => $pin,
                ]);
            }

            $pin->setTitle(trim((string) $title));

            $pin->setDescription(
                $description !== null && trim((string) $description) !== ''
                    ? trim((string) $description)
                    : null
            );

            // Associer le Pin à l'utilisateur connecté
            $pin->setUser($user);

            // Récupérer l'image
            $image = $request->files->get('image');

            if (!$image instanceof UploadedFile) {
                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner une image.'
                );

                return $this->render('pin/create.html.twig', [
                    'pin' => $pin,
                ]);
            }

            // Formats autorisés
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/webp',
            ];

            if (!in_array(
                $image->getMimeType(),
                $allowedMimeTypes,
                true
            )) {
                $this->addFlash(
                    'danger',
                    'Format d’image non autorisé. Utilisez JPG, PNG ou WEBP.'
                );

                return $this->render('pin/create.html.twig', [
                    'pin' => $pin,
                ]);
            }

            $extension = $image->guessExtension();

            if (!$extension) {
                $this->addFlash(
                    'danger',
                    'Impossible de déterminer le format de l’image.'
                );

                return $this->render('pin/create.html.twig', [
                    'pin' => $pin,
                ]);
            }

            // Nom unique
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

            // Dossier des images
            $uploadDirectory = $this->getParameter(
                'kernel.project_dir'
            ) . '/public/uploads/pins';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            // Déplacer l'image
            $image->move(
                $uploadDirectory,
                $fileName
            );

            // Enregistrer le nom
            $pin->setImageName($fileName);

            $entityManager->persist($pin);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre Pin a été créé avec succès.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        return $this->render(
            'pin/create.html.twig',
            [
                'pin' => $pin,
            ]
        );
    }


    #[Route('/pin/{id}/show', name: 'app_pin_show')]
    public function show(
        int $id,
        PinRepository $pinRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $pin = $pinRepository->find($id);

        // Pin inexistant
        if (!$pin) {
            $this->addFlash(
                'danger',
                'Le Pin que vous recherchez n’existe pas ou a été supprimé.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        // Un utilisateur ne peut voir que son propre Pin
        if ($pin->getUser() !== $user) {
            $this->addFlash(
                'danger',
                'Vous ne pouvez pas accéder à ce Pin.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        return $this->render(
            'pin/show.html.twig',
            [
                'pin' => $pin,
            ]
        );
    }


    #[Route('/pin/{id}/edit', name: 'app_pin_edit')]
    public function edit(
        int $id,
        Request $request,
        PinRepository $pinRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $pin = $pinRepository->find($id);

        // Pin inexistant
        if (!$pin) {
            $this->addFlash(
                'danger',
                'Le Pin que vous recherchez n’existe pas ou a été supprimé.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        // Seul le propriétaire peut modifier
        if ($pin->getUser() !== $user) {
            $this->addFlash(
                'danger',
                'Vous ne pouvez pas modifier ce Pin.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');

            // Vérification du titre
            if ($title === null || trim((string) $title) === '') {
                $this->addFlash(
                    'danger',
                    'Le titre du Pin est obligatoire.'
                );

                return $this->render('pin/edit.html.twig', [
                    'pin' => $pin,
                ]);
            }

            // Minimum 3 caractères
            if (mb_strlen(trim((string) $title)) < 3) {
                $this->addFlash(
                    'danger',
                    'Le titre doit contenir au moins 3 caractères.'
                );

                return $this->render('pin/edit.html.twig', [
                    'pin' => $pin,
                ]);
            }

            $pin->setTitle(
                trim((string) $title)
            );

            $pin->setDescription(
                $description !== null && trim((string) $description) !== ''
                    ? trim((string) $description)
                    : null
            );

            // Nouvelle image éventuelle
            $image = $request->files->get('image');

            if ($image instanceof UploadedFile) {

                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ];

                if (!in_array(
                    $image->getMimeType(),
                    $allowedMimeTypes,
                    true
                )) {
                    $this->addFlash(
                        'danger',
                        'Format d’image non autorisé. Utilisez JPG, PNG ou WEBP.'
                    );

                    return $this->render('pin/edit.html.twig', [
                        'pin' => $pin,
                    ]);
                }

                $extension = $image->guessExtension();

                if (!$extension) {
                    $this->addFlash(
                        'danger',
                        'Impossible de déterminer le format de l’image.'
                    );

                    return $this->render('pin/edit.html.twig', [
                        'pin' => $pin,
                    ]);
                }

                // Supprimer l'ancienne image
                if ($pin->getImageName()) {

                    $oldImagePath = $this->getParameter(
                        'kernel.project_dir'
                    ) . '/public/uploads/pins/'
                        . $pin->getImageName();

                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Nouveau nom
                $fileName = bin2hex(random_bytes(16))
                    . '.' . $extension;

                $uploadDirectory = $this->getParameter(
                    'kernel.project_dir'
                ) . '/public/uploads/pins';

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }

                $image->move(
                    $uploadDirectory,
                    $fileName
                );

                $pin->setImageName($fileName);
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre Pin a été modifié avec succès.'
            );

            return $this->redirectToRoute(
                'app_pin_show',
                [
                    'id' => $pin->getId(),
                ]
            );
        }

        return $this->render(
            'pin/edit.html.twig',
            [
                'pin' => $pin,
            ]
        );
    }


    #[Route('/pin/{id}/delete', name: 'app_pin_delete')]
    public function delete(
        int $id,
        PinRepository $pinRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $pin = $pinRepository->find($id);

        // Pin inexistant
        if (!$pin) {
            $this->addFlash(
                'danger',
                'Le Pin que vous recherchez n’existe pas ou a déjà été supprimé.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        // Seul le propriétaire peut supprimer
        if ($pin->getUser() !== $user) {
            $this->addFlash(
                'danger',
                'Vous ne pouvez pas supprimer ce Pin.'
            );

            return $this->redirectToRoute(
                'app_pin_index'
            );
        }

        // Supprimer l'image
        if ($pin->getImageName()) {

            $imagePath = $this->getParameter(
                'kernel.project_dir'
            ) . '/public/uploads/pins/'
                . $pin->getImageName();

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($pin);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Votre Pin a été supprimé avec succès.'
        );

        return $this->redirectToRoute(
            'app_pin_index'
        );
    }
}
