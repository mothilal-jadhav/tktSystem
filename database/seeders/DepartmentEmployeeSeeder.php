<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Demo employees for local development. Every name is made up and every
 * email uses the fake @fakemail.com domain. Managers are not included.
 */
class DepartmentEmployeeSeeder extends Seeder
{
    /**
     * Where the generated passwords are saved, on the private "local" disk
     * (storage/app/private, which is git-ignored).
     */
    public const PASSWORDS_FILE = 'seeded-users.csv';

    /**
     * Employees per department.
     *
     * @var array<string, list<string>>
     */
    public const EMPLOYEES = [
        Department::HR => [
            'Ananya Iyer',
            'Harpreet Kaur',
            'Moushumi Das',
        ],
        Department::SALES => [
            'Rohan Deshmukh',
            'Kavya Reddy',
            'Arjun Nair',
            'Imran Qureshi',
            'Tenzin Norbu',
            'Priyanka Chatterjee',
            'Vikram Rathore',
            'Siddharth Joshi',
            'Neha Agarwal',
            'Gurpreet Singh Sandhu',
        ],
        Department::IT => [
            'Sneha Kulkarni',
            'Karthik Subramanian',
            'Fatima Sheikh',
            'Aditya Banerjee',
            'Meera Pillai',
            'Pradeep Hegde',
            'Ritu Saxena',
            'Nilesh Patel',
            'Lakshmi Venkatesh',
            'Shalini Mohanty',
        ],
        Department::WELLNESS => [
            'Divya Menon',
            'Rahul Verma',
            'Zoya Khan',
            'Suresh Gowda',
            'Pooja Bhatt',
            'Anil Kumar Yadav',
            'Jasleen Kaur Gill',
            'Abhishek Pandey',
            'Rekha Naidu',
            'Thomas Mathew',
        ],
        Department::CLAIMS => [
            'Aishwarya Rao',
            'Farhan Siddiqui',
            'Nandini Sharma',
            'Bhavana Shetty',
            'Ramesh Chauhan',
            'Swati Ghosh',
            'Gayatri Krishnan',
            'Yashwant Pawar',
            'Anjali Dsouza',
            'Tanvi Desai',
        ],
        Department::ENDORSEMENT => [
            'Shreya Pandit',
            'Naveen Chandra',
            'Ayesha Mirza',
            'Deepak Malhotra',
            'Ishita Sen',
            'Varun Kapoor',
            'Mohan Das',
            'Sunita Lepcha',
            'Rajesh Bora',
            'Omkar Sawant',
        ],
    ];

    /**
     * Create any of the employees that don't exist yet.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('Demo employees are not created in production.');

            return;
        }

        $employee = Role::where('name', Role::EMPLOYEE)->sole();
        $rows = [];
        $skipped = 0;

        foreach (self::EMPLOYEES as $departmentName => $names) {
            $department = Department::where('name', $departmentName)->sole();

            foreach ($names as $name) {
                $email = strtolower((string) preg_replace('/[^A-Za-z]/', '', $name)).'@fakemail.com';

                // Users in the bin count too, so re-running never duplicates or restores anyone.
                if (User::withTrashed()->where('email', $email)->exists()) {
                    $skipped++;

                    continue;
                }

                $password = Str::password(16);

                // The same steps as UserController::store: role and department are set explicitly.
                $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
                $user->must_change_password = true;
                $user->role()->associate($employee);
                $user->department()->associate($department);
                $user->save();

                $rows[] = [$name, $email, $departmentName, $password];
            }
        }

        $this->savePasswords($rows);

        $this->command->info(sprintf(
            'Created %d employees (%d already existed).%s',
            count($rows),
            $skipped,
            $rows === [] ? '' : ' Passwords saved to storage/app/private/'.self::PASSWORDS_FILE.'.',
        ));
    }

    /**
     * Append the new users' passwords to the CSV file, adding a header if the file is new.
     * Passwords are never written to the log.
     *
     * @param  list<list<string>>  $rows
     */
    private function savePasswords(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists(self::PASSWORDS_FILE)) {
            $rows = [['name', 'email', 'department', 'password'], ...$rows];
        }

        $disk->append(self::PASSWORDS_FILE, rtrim($this->toCsv($rows), "\n"));
    }

    /**
     * Generated passwords can contain commas and quotes, so fputcsv does the escaping.
     *
     * @param  list<list<string>>  $rows
     */
    private function toCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Could not open a temporary stream for the passwords file.');
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, escape: '');
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
