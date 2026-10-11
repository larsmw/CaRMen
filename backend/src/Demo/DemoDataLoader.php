<?php

namespace App\Demo;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Sample accounts, contacts, deals and activities for trying out the CRM.
 * Used by the installer, so unlike AppFixtures it works in prod and creates no
 * extra users (no logins with well-known passwords) — everything is owned by
 * the given user. The caller flushes.
 */
class DemoDataLoader
{
    private const ACCOUNTS = [
        'acme'    => ['Acme Corp', 'Technology', 'https://acme.example.com', 'Copenhagen', 'DK', 500],
        'globex'  => ['Globex Inc', 'Manufacturing', 'https://globex.example.com', 'Hamburg', 'DE', 1200],
        'initech' => ['Initech', 'Software', 'https://initech.example.com', 'Aarhus', 'DK', 80],
        'umbrella'=> ['Umbrella Health', 'Healthcare', null, 'Malmö', 'SE', 3000],
    ];

    private const CONTACTS = [
        'alice' => ['Alice', 'Smith', 'alice@acme.example.com', 'CTO', 'acme', 'customer'],
        'carl'  => ['Carl', 'Hansen', 'carl@acme.example.com', 'Head of IT', 'acme', 'customer'],
        'bob'   => ['Bob', 'Jones', 'bob@globex.example.com', 'Procurement Manager', 'globex', 'prospect'],
        'dina'  => ['Dina', 'Berg', 'dina@initech.example.com', 'CEO', 'initech', 'lead'],
        'erik'  => ['Erik', 'Lund', 'erik@umbrella.example.com', 'Operations Director', 'umbrella', 'prospect'],
        'freja' => ['Freja', 'Holm', 'freja@example.com', 'Consultant', null, 'lead'],
    ];

    // title, account, contact, value, stage, probability, close date (relative)
    private const DEALS = [
        'acmeLicense'  => ['Acme Enterprise License', 'acme', 'alice', '85000.00', Deal::STAGE_PROPOSAL, 60, '+30 days'],
        'acmeSupport'  => ['Acme Support Renewal', 'acme', 'carl', '18000.00', Deal::STAGE_CLOSED_WON, 100, '-10 days'],
        'globexPilot'  => ['Globex Pilot Project', 'globex', 'bob', '12000.00', Deal::STAGE_QUALIFICATION, 30, '+45 days'],
        'initechSetup' => ['Initech Onboarding', 'initech', 'dina', '7500.00', Deal::STAGE_PROSPECTING, 10, '+60 days'],
        'umbrellaRoll' => ['Umbrella Group Rollout', 'umbrella', 'erik', '140000.00', Deal::STAGE_NEGOTIATION, 75, '+14 days'],
    ];

    // type, subject, status, contact, deal, scheduled (relative)
    private const ACTIVITIES = [
        [Activity::TYPE_MEETING, 'Demo call with Alice', Activity::STATUS_PLANNED, 'alice', 'acmeLicense', '+2 days 10:00'],
        [Activity::TYPE_CALL, 'Follow up on pilot scope', Activity::STATUS_PLANNED, 'bob', 'globexPilot', '+1 day 14:00'],
        [Activity::TYPE_EMAIL, 'Send pricing proposal', Activity::STATUS_COMPLETED, 'erik', 'umbrellaRoll', '-3 days 09:00'],
        [Activity::TYPE_TASK, 'Prepare contract draft', Activity::STATUS_PLANNED, 'erik', 'umbrellaRoll', '+3 days 12:00'],
        [Activity::TYPE_NOTE, 'Dina prefers contact by phone', Activity::STATUS_COMPLETED, 'dina', null, '-1 day 16:00'],
    ];

    public function __construct(private EntityManagerInterface $em) {}

    public function load(User $owner): void
    {
        $accounts = [];
        foreach (self::ACCOUNTS as $key => [$name, $industry, $website, $city, $country, $employees]) {
            $accounts[$key] = (new Account())
                ->setName($name)
                ->setIndustry($industry)
                ->setWebsite($website)
                ->setCity($city)
                ->setCountry($country)
                ->setEmployeeCount($employees);
            $this->em->persist($accounts[$key]);
        }

        $contacts = [];
        foreach (self::CONTACTS as $key => [$first, $last, $email, $title, $account, $status]) {
            $contacts[$key] = (new Contact())
                ->setFirstName($first)
                ->setLastName($last)
                ->setEmail($email)
                ->setJobTitle($title)
                ->setAccount($account ? $accounts[$account] : null)
                ->setStatus($status);
            $this->em->persist($contacts[$key]);
        }

        $deals = [];
        foreach (self::DEALS as $key => [$title, $account, $contact, $value, $stage, $probability, $close]) {
            $deals[$key] = (new Deal())
                ->setTitle($title)
                ->setAccount($accounts[$account])
                ->setPrimaryContact($contacts[$contact])
                ->setOwner($owner)
                ->setValue($value)
                ->setStage($stage)
                ->setProbability($probability)
                ->setCloseDate(new \DateTime($close));
            $this->em->persist($deals[$key]);
        }

        foreach (self::ACTIVITIES as [$type, $subject, $status, $contact, $deal, $when]) {
            $activity = (new Activity())
                ->setType($type)
                ->setSubject($subject)
                ->setStatus($status)
                ->setContact($contacts[$contact])
                ->setDeal($deal ? $deals[$deal] : null)
                ->setAssignedTo($owner)
                ->setScheduledAt(new \DateTime($when));
            if ($status === Activity::STATUS_COMPLETED) {
                $activity->setCompletedAt(new \DateTime($when));
            }
            $this->em->persist($activity);
        }
    }
}
