<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Image',
                'required' => true,
                'constraints' => [
                    new Image([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG et WebP uniquement.',
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Votre image doit faire moins de {{ limit }} Mo.',
                    ]),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
        ;

        if ($options['is_admin']) {
            $builder
                ->add('user', EntityType::class, [
                    'label' => 'Utilisateur',
                    'required' => true,
                    'class' => User::class,
                    'choice_label' => 'name',
                    'placeholder' => '— Sélectionner un utilisateur —',
                    'choice_attr' => function (User $user): array {
                        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

                        return [
                            'data-is-admin' => $isAdmin ? '1' : '0',
                        ];
                    },
                    'query_builder' => function (UserRepository $userRepository): QueryBuilder {
                        return $userRepository->createQueryBuilder('u')
                            ->orderBy('u.name', 'ASC');
                    },
                ])
                ->add('album', EntityType::class, [
                    'label' => 'Album',
                    'required' => true,
                    'class' => Album::class,
                    'choice_label' => 'name',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_admin' => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
