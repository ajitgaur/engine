<?php

namespace Spec\Minds\Core\Features;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager;
use Minds\Core\Features\Services\ServiceInterface;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var ServiceInterface */
    protected $service1;

    /** @var ServiceInterface */
    protected $service2;

    /** @var ActiveSession */
    protected $activeSession;

    public function let(
        ServiceInterface $service1,
        ServiceInterface $service2,
        ActiveSession $activeSession
    ) {
        $this->service1 = $service1;
        $this->service2 = $service2;
        $this->activeSession = $activeSession;

        $this->beConstructedWith(
            [ $service1, $service2 ],
            $activeSession
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_false_if_a_feature_does_not_exist(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->has('feature99-non-existant')
            ->shouldReturn(false);
    }

    public function it_should_return_false_if_a_feature_exists_and_it_is_deactivated(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->has('feature3')
            ->shouldReturn(false);
    }

    public function it_should_return_true_if_a_feature_exists_and_it_is_activated(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->has('feature2')
            ->shouldReturn(true);
    }

    public function it_should_export_a_merge_of_all_features(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->export()
            ->shouldReturn([
                'feature1' => true,
                'feature2' => true,
                'feature3' => false,
            ]);
    }
}
