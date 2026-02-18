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
    // Accumulates the parsed homeowner records as associative arrays
    $output = [];

    foreach ($names as $homeownerString) {
        // Split a raw homeowner string into one or more people (handles "and" / "&")
        $fakers = splitHomeownerString($homeownerString);

        // Running placeholders for the most recent first/last names we discover
        $firstName = null;
        $lastName = null;

        // Break each person's string into segments: [title, (first|initial)?, last?]
        $fakers = array_map(fn (string $name) => explode(' ', $name), $fakers);

        // A couple like "Mr and Mrs Smith" starts with a single-word first entry (only a title)
        $isCouple = count($fakers[0]) === 1;

        // Process from right-to-left so the last names take precedence for shared surnames
        foreach (array_reverse($fakers) as $index => $nameSegments) {
            // Full-name has three parts: title, first/initial, last
            $isFullName = count($nameSegments) === 3;

            // If only a title is present, reuse the most recent last name discovered; otherwise update it
            if (count($nameSegments) === 1) {
                $nameSegments[] = $lastName;
            } else {
                $lastName = $nameSegments[2] ?? $nameSegments[1];
            }

            // Capture/retain the first name only when we have a full name on this iteration
            $firstName = $isFullName ? $nameSegments[1] : $firstName;

            // Determine whether the first token represents an initial (e.g. "J" or "J.")
            $hasInitial = $firstName && (
                strlen($firstName) === 1 || str_contains($firstName, '.')
            );

            $output[] = [
                'title' => $nameSegments[0],
                // Omit first_name if it's an initial or if it's the first of a couple (e.g. "Mr and Mrs Smith")
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
