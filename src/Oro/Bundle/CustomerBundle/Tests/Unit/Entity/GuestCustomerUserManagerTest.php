<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Tests\Unit\Entity\Stub\WebsiteStub as Website;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class GuestCustomerUserManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var GuestCustomerUserManager */
    private $guestCustomerUserManager;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CustomerUserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserManager;

    /** @var DefaultUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultUserProvider;

    /** @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $relationsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->customerUserManager = $this->createMock(CustomerUserManager::class);
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);

        $this->guestCustomerUserManager = new GuestCustomerUserManager(
            $this->websiteManager,
            $this->customerUserManager,
            $this->relationsProvider,
            $this->defaultUserProvider,
            new PropertyAccessor()
        );
    }

    public function testGenerateGuestCustomerUser(): void
    {
        $this->customerUserManager
            ->expects($this->once())
            ->method('generatePassword')
            ->with(10)
            ->willReturn('1234567890');

        $owner = new User();
        $owner->setFirstName('owner name');

        $this->customerUserManager
            ->expects($this->once())
            ->method('updatePassword');

        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('oro_customer', 'default_customer_owner')
            ->willReturn($owner);

        $website = new Website();
        $website->setName('Default Website');
        $website->setOrganization(new Organization());
        $website->setDefaultRole(new CustomerUserRole());
        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $group = new CustomerGroup();
        $group->setOwner(new User());
        $group->setName('Default Group');
        $this->relationsProvider
            ->expects($this->once())
            ->method('getCustomerGroup')
            ->willReturn($group);

        $customerUser = $this->guestCustomerUserManager->generateGuestCustomerUser([
            'email' => 'test@example.com',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
        ]);

        $this->assertEquals('test@example.com', $customerUser->getEmail());
        $this->assertEquals('First Name', $customerUser->getFirstName());
        $this->assertEquals('Last Name', $customerUser->getLastName());
        $this->assertTrue($customerUser->isGuest());
        $this->assertFalse($customerUser->isConfirmed());
        $this->assertFalse($customerUser->isEnabled());
        $this->assertEquals('Default Website', $customerUser->getWebsite()->getName());
        $this->assertEquals('Default Group', $customerUser->getCustomer()->getGroup()->getName());
        $this->assertEquals($owner->getFirstName(), $customerUser->getOwner()->getFirstName());
        $this->assertSame($customerUser->getRoles(), [$website->getDefaultRole()]);
    }
}
