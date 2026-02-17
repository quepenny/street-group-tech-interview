<?php

$filename = $argv[1] ?? 'homeowners.csv';
$names = loadNamesFromCsv($filename);
$output = parseHomeownerNames($names);

echo json_encode($output, JSON_PRETTY_PRINT) . "\n";

function loadNamesFromCsv(string $filename): array
{
    if (!file_exists($filename)) {
        echo "Error: File '$filename' does not exist.\n";
        exit(1);
    }

    return array_slice(array_map(
        fn (string $line): string => str_getcsv($line, escape: '\\')[0],
        file($filename)
    ), 1);
}

function parseHomeownerNames(array $names): array
{
    $output = [];

    foreach ($names as $homeownerString) {
        $fakers = splitHomeownerString($homeownerString);

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

    return $output;
}

function splitHomeownerString(string $homeownerString): array
{
    if (str_contains($homeownerString, 'and')) {
        return explode(' and ', $homeownerString);
    }

    if (str_contains($homeownerString, '&')) {
        return explode(' & ', $homeownerString);
    }

    return [$homeownerString];
}
