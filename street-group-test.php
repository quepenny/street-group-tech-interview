<?php

$fakeHomeOwnersFilename = $argv[1] ?? 'examples.csv';

if (!file_exists($fakeHomeOwnersFilename)) {
    echo "Error: File '$fakeHomeOwnersFilename' does not exist.\n";

    exit(1);
}

$fakeHomeOwners = array_slice(array_map(
    fn (string $line): string => str_getcsv($line, escape: '\\')[0],
    file($fakeHomeOwnersFilename)
), 1);

$output = [];

foreach ($fakeHomeOwners as $fakers) {
    if (str_contains($fakers, 'and')) {
        $fakers = explode(' and ', $fakers);
    } elseif (str_contains($fakers, '&')) {
        $fakers = explode(' & ', $fakers);
    } else {
        $fakers = [$fakers];
    }

    $firstName = null;
    $lastName = null;

    $fakers = array_map(fn (string $name) => explode(' ', $name), $fakers);
    $isCouple = count($fakers[0]) === 1;

    // Reverse the order of the fakers so the last occurring first and last names are prioritised.
    foreach (array_reverse($fakers) as $index => $nameSegments) {
        $isFullName = count($nameSegments) === 3;

        if (count($nameSegments) === 1) {
            $nameSegments[] = $lastName;
        } else {
            $lastName = $nameSegments[2] ?? $nameSegments[1];
        }

        $firstName = $isFullName ? $nameSegments[1] : $firstName;

        $hasInitial = $firstName && (
            strlen($firstName) === 1 || str_contains($firstName, '.')
        );

        $output[] = [
            'title' => $nameSegments[0],
            'first_name' => $hasInitial || $isCouple && $index === 0 ? null : $firstName,
            'last_name' => $lastName,
            'initial' => $hasInitial ? $firstName[0] : null
        ];
    }
}

echo json_encode($output, JSON_PRETTY_PRINT) . "\n";