<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    private ?Department $department = null;

    #[ORM\Column(length: 50)]
    private ?string $employmentStatus = 'active';

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    // Personal Details
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mobile = null;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    private ?string $nationalId = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $nationalIdIssueDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $nationalIdExpiryDate = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    // Bank Details
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bankName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bankIdentifierCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bankBranch = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bankAccountNumber = null;

    // Work Details
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $workType = null; // e.g., 'office', 'remote'

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $shift = null; // e.g., 'regular', 'night'

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: Contract::class, cascade: ['persist', 'remove'])]
    private Collection $contracts;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeRequest::class, cascade: ['persist', 'remove'])]
    private Collection $requests;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    // New Fields
    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $badgeId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $maritalStatus = null;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: FamilyMember::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $familyMembers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $employeeRole = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $manager = null;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    private ?EmploymentType $employmentType = null;

    #[ORM\ManyToMany(targetEntity: EmployeeTag::class, inversedBy: 'employees')]
    private Collection $tags;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $workLocation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $workEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $workPhone = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $joiningDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $contractEndDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $basicSalary = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $iban = null;

    #[ORM\OneToOne(inversedBy: 'employee', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
        $this->contracts = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->familyMembers = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setBadgeIdValue(): void
    {
        if ($this->badgeId === null) {
             // Simple unique ID generation relative to time to avoid collisions in a simple way
             // In a real app with high concurrency, a DB sequence or UUID is better.
             $this->badgeId = 'EMP-' . strtoupper(substr(uniqid(), -6));
        }
    }

    // ... Getters and Setters ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNationalId(): ?string
    {
        return $this->nationalId;
    }

    public function setNationalId(?string $nationalId): static
    {
        $this->nationalId = $nationalId;
        return $this;
    }

    public function getNationalIdIssueDate(): ?\DateTimeImmutable
    {
        return $this->nationalIdIssueDate;
    }

    public function setNationalIdIssueDate(?\DateTimeImmutable $nationalIdIssueDate): static
    {
        $this->nationalIdIssueDate = $nationalIdIssueDate;
        return $this;
    }

    public function getNationalIdExpiryDate(): ?\DateTimeImmutable
    {
        return $this->nationalIdExpiryDate;
    }

    public function setNationalIdExpiryDate(?\DateTimeImmutable $nationalIdExpiryDate): static
    {
        $this->nationalIdExpiryDate = $nationalIdExpiryDate;
        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setEmployee($this);
        }
        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            if ($contract->getEmployee() === $this) {
                $contract->setEmployee(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(EmployeeDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setEmployee($this);
        }
        return $this;
    }

    public function removeDocument(EmployeeDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getEmployee() === $this) {
                $document->setEmployee(null);
            }
        }
        return $this;
    }
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getEmploymentStatus(): ?string
    {
        return $this->employmentStatus;
    }

    public function setEmploymentStatus(string $employmentStatus): static
    {
        $this->employmentStatus = $employmentStatus;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getBankIdentifierCode(): ?string
    {
        return $this->bankIdentifierCode;
    }

    public function setBankIdentifierCode(?string $bankIdentifierCode): static
    {
        $this->bankIdentifierCode = $bankIdentifierCode;

        return $this;
    }

    public function getBankBranch(): ?string
    {
        return $this->bankBranch;
    }

    public function setBankBranch(?string $bankBranch): static
    {
        $this->bankBranch = $bankBranch;

        return $this;
    }

    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(?string $bankAccountNumber): static
    {
        $this->bankAccountNumber = $bankAccountNumber;

        return $this;
    }

    public function getWorkType(): ?string
    {
        return $this->workType;
    }

    public function setWorkType(?string $workType): static
    {
        $this->workType = $workType;

        return $this;
    }

    public function getShift(): ?string
    {
        return $this->shift;
    }

    public function setShift(?string $shift): static
    {
        $this->shift = $shift;

        return $this;
    }



    /**
     * @return Collection<int, EmployeeRequest>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(EmployeeRequest $request): static
    {
        if (!$this->requests->contains($request)) {
            $this->requests->add($request);
            $request->setEmployee($this);
        }

        return $this;
    }

    public function removeRequest(EmployeeRequest $request): static
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getEmployee() === $this) {
                $request->setEmployee(null);
            }
        }

        return $this;
    }

    public function getBadgeId(): ?string
    {
        return $this->badgeId;
    }

    public function setBadgeId(?string $badgeId): static
    {
        $this->badgeId = $badgeId;
        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;
        return $this;
    }

    public function getMaritalStatus(): ?string
    {
        return $this->maritalStatus;
    }

    public function setMaritalStatus(?string $maritalStatus): static
    {
        $this->maritalStatus = $maritalStatus;
        return $this;
    }

    /**
     * @return Collection<int, FamilyMember>
     */
    public function getFamilyMembers(): Collection
    {
        return $this->familyMembers;
    }

    public function addFamilyMember(FamilyMember $familyMember): static
    {
        if (!$this->familyMembers->contains($familyMember)) {
            $this->familyMembers->add($familyMember);
            $familyMember->setEmployee($this);
        }
        return $this;
    }

    public function removeFamilyMember(FamilyMember $familyMember): static
    {
        if ($this->familyMembers->removeElement($familyMember)) {
            if ($familyMember->getEmployee() === $this) {
                $familyMember->setEmployee(null);
            }
        }
        return $this;
    }

    public function getEmployeeRole(): ?string
    {
        return $this->employeeRole;
    }

    public function setEmployeeRole(?string $employeeRole): static
    {
        $this->employeeRole = $employeeRole;
        return $this;
    }

    public function getManager(): ?self
    {
        return $this->manager;
    }

    public function setManager(?self $manager): static
    {
        $this->manager = $manager;
        return $this;
    }

    public function getEmploymentType(): ?EmploymentType
    {
        return $this->employmentType;
    }

    public function setEmploymentType(?EmploymentType $employmentType): static
    {
        $this->employmentType = $employmentType;
        return $this;
    }

    /**
     * @return Collection<int, EmployeeTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(EmployeeTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(EmployeeTag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getWorkLocation(): ?string
    {
        return $this->workLocation;
    }

    public function setWorkLocation(?string $workLocation): static
    {
        $this->workLocation = $workLocation;
        return $this;
    }

    public function getWorkEmail(): ?string
    {
        return $this->workEmail;
    }

    public function setWorkEmail(?string $workEmail): static
    {
        $this->workEmail = $workEmail;
        return $this;
    }

    public function getWorkPhone(): ?string
    {
        return $this->workPhone;
    }

    public function setWorkPhone(?string $workPhone): static
    {
        $this->workPhone = $workPhone;
        return $this;
    }

    public function getJoiningDate(): ?\DateTimeImmutable
    {
        return $this->joiningDate;
    }

    public function setJoiningDate(?\DateTimeImmutable $joiningDate): static
    {
        $this->joiningDate = $joiningDate;
        return $this;
    }

    public function getContractEndDate(): ?\DateTimeImmutable
    {
        return $this->contractEndDate;
    }

    public function setContractEndDate(?\DateTimeImmutable $contractEndDate): static
    {
        $this->contractEndDate = $contractEndDate;
        return $this;
    }

    public function getBasicSalary(): ?string
    {
        return $this->basicSalary;
    }

    public function setBasicSalary(?string $basicSalary): static
    {
        $this->basicSalary = $basicSalary;
        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
