<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_sites\GroupSitesNegotiator;
use Drupal\user\UserInterface;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test the Group Sites negotiator.
 *
 * @coversDefaultClass \Drupal\group_sites\GroupSitesNegotiator
 * @group group_sites
 */
class GroupSitesNegotiatorTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ConfigFactoryInterface>
   */
  protected $configFactory;

  /**
   * The mocked context repository.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextRepositoryInterface>
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
  }

  /**
   * Tests getting the active group without context provider.
   *
   * @covers ::getActiveGroup
   */
  public function testWithoutContextProvider(): void {
    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn(NULL);
    $this->configFactory
      ->get('group_sites.settings')
      ->shouldBeCalledOnce()
      ->willReturn($config->reveal());

    $this->assertNull($this->setUpNegotiator()->getActiveGroup());
  }

  /**
   * Test getting the active group without context.
   *
   * @covers ::getActiveGroup
   */
  public function testWithoutContext(): void {
    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $this->configFactory
      ->get('group_sites.settings')
      ->shouldBeCalledOnce()
      ->willReturn($config->reveal());

    $this->contextRepository
      ->getRuntimeContexts(['@some_service:some_context'])
      ->shouldBeCalledOnce()
      ->willReturn([]);

    $this->assertNull($this->setUpNegotiator()->getActiveGroup());
  }

  /**
   * Test getting the active group with group context.
   *
   * @covers ::getActiveGroup
   */
  public function testWithGroupContext(): void {
    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $this->configFactory
      ->get('group_sites.settings')
      ->shouldBeCalledOnce()
      ->willReturn($config->reveal());

    $group = $this->prophesize(GroupInterface::class)->reveal();
    $this->contextRepository
      ->getRuntimeContexts(['@some_service:some_context'])
      ->shouldBeCalledOnce()
      ->willReturn([$this->setUpContext($group)]);

    $this->assertSame($group, $this->setUpNegotiator()->getActiveGroup());
  }

  /**
   * Test exception with non-group context.
   *
   * @covers ::getActiveGroup
   */
  public function testWithOtherContext(): void {
    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $this->configFactory
      ->get('group_sites.settings')
      ->shouldBeCalledOnce()
      ->willReturn($config->reveal());

    $this->contextRepository
      ->getRuntimeContexts(['@some_service:some_context'])
      ->shouldBeCalledOnce()
      ->willReturn([$this->setUpContext($this->prophesize(UserInterface::class)->reveal())]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Context value is not a Group entity.');
    $this->setUpNegotiator()->getActiveGroup();
  }

  /**
   * Sets up the negotiator to run tests on.
   *
   * @return \Drupal\group_sites\GroupSitesNegotiator
   *   The negotiator to run tests on.
   */
  protected function setUpNegotiator(): GroupSitesNegotiator {
    return new GroupSitesNegotiator(
      $this->configFactory->reveal(),
      $this->contextRepository->reveal()
    );
  }

  /**
   * Sets up a mock context.
   *
   * @param mixed $value
   *   The value the mocked context should return.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextInterface>
   *   The mocked context.
   */
  protected function setUpContext(mixed $value): ObjectProphecy {
    $context = $this->prophesize(ContextInterface::class);
    $context->getContextValue()->willReturn($value);
    $context->getCacheContexts()->willReturn([]);
    $context->getCacheTags()->willReturn([]);
    $context->getCacheMaxAge()->willReturn(-1);
    return $context;
  }

}
