<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Form\Type;

use Mautic\ConfigBundle\Form\Helper\RestrictionHelper;
use ReflectionClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @var RestrictionHelper
     */
    private $restrictionHelper;

    /**
     * @param RestrictionHelper $restrictionHelper
     */
    public function __construct(RestrictionHelper $restrictionHelper)
    {
        $this->restrictionHelper = $restrictionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data'] as $config) {
            if (isset($config['formAlias']) && !empty($config['parameters'])) {
                $checkThese = array_intersect(array_keys($config['parameters']), $options['fileFields']);
                foreach ($checkThese as $checkMe) {
                    // Unset base64 encoded values
                    unset($config['parameters'][$checkMe]);
                }
                $builder->add(
                    $this->generateFormName($config),
                    $config['formAlias'],
                    [
                        'data' => $config['parameters'],
                    ]
                );
            }
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                foreach ($form as $config => $configForm) {
                    foreach ($configForm as $child) {
                        $this->restrictionHelper->applyRestrictions($child, $configForm);
                    }
                }
            }
        );

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'apply_onclick' => 'Mautic.activateBackdrop()',
                'save_onclick'  => 'Mautic.activateBackdrop()',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'fileFields' => [],
            ]
        );
    }

    /**
     * Builds form name out of provided bundle and form class name (formAlias).
     *
     * @param array $config
     *
     * @return string
     */
    private function generateFormName(array $config)
    {
        // @deprecated This condition can be deleted once Mautic uses Symfony 3 and all formAliases are class names.
        if (!class_exists($config['formAlias'])) {
            return $config['formAlias'];
        }

        $reflection = new ReflectionClass($config['formAlias']);

        return "{$config['bundle']}_{$reflection->getShortName()}";
    }
}
