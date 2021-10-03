<?php

namespace maranqz\StockBundle\Form\Type;

use maranqz\StockBundle\Entity\Stock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateStockType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sku', TextType::class)
            ->add('branch', TextType::class)
            ->add('count', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('save', SubmitType::class)
            ->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
            'empty_data' => function (FormInterface $form) {
                return new Stock(
                    (string)$form->get('sku')->getData(),
                    (string)$form->get('branch')->getData()
                );
            }
        ]);
    }

    /**
     * @param Stock|null $viewData
     */
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Stock) {
            throw new UnexpectedTypeException($viewData, Stock::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $forms['sku']->setData($viewData->getSku());
        $forms['branch']->setData($viewData->getBranch());
        $forms['count']->setData($viewData->getCount());
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        /** @var Stock $viewData */
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $viewData->setCount($forms['count']->getData());
    }
}
