<?php

namespace spec\Pim\Bundle\MagentoConnectorBundle\Manager;
use Pim\Bundle\CatalogBundle\Manager\CurrencyManager as BaseCurrencyManager;

use Pim\Bundle\CatalogBundle\Entity\Currency;
use Pim\Bundle\CatalogBundle\Entity\Repository\CurrencyRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CurrencyManagerSpec extends ObjectBehavior
{
    function let(CurrencyRepository $currencyRepository)
    {
        $this->beConstructedWith($currencyRepository);
    }

    function it_gives_currency_choices(CurrencyRepository $currencyRepository, Currency $currency)
    {
        $currencyRepository->findBy(['activated' => true])->willReturn([$currency]);
        $currency->getCode()->willReturn('EUR');

        $this->getCurrencyChoices()->shouldReturn(['EUR' => 'EUR']);
    }

    function it_return_active_code_choices(CurrencyRepository $currencyRepository, Currency $currency)
    {
        $currencyRepository->findBy(['activated' => true])->willReturn([$currency]);
        $currency->getCode()->willReturn('EUR');

        $this->getActiveCodeChoices()->shouldReturn(['EUR' => 'EUR']);
    }

    function it_return_empty_array_when_active_code_choices_not_found(CurrencyRepository $currencyRepository, Currency $currency)
    {
        $currencyRepository->findBy(['activated' => true])->willReturn([]);
        $currency->getCode()->willReturn([]);

        $this->getActiveCodeChoices()->shouldReturn([]);
    }
}
