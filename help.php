<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tally Help</title>
    <style>
        * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
        table {
            margin: 16px;
            border: 1px solid black;
        }
        table tr {
            border: 1px solid black;
        }
    </style>
</head>
<body>

<h3>The URL is your friend.</h3>

<table>
    <thead>
    <tr>
        <td>Command</td>
        <td>What does it do</td>
        <td>Notes</td>
        <td>Example</td>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>start=YYYY-MM-DD</td>
        <td>Set the start date in year-month-day format</td>
        <td>If you don't provide an end date with the start date, it'll use start + 1 month</td>
        <td>?start=2015-01-01</td>
    </tr>
    <tr>
        <td>end=YYYY-MM-DD</td>
        <td>Set the end date in YYYY-MM-DD format.</td>
        <td>You MUST provide a start date with an end date.</td>
        <td>?start=2015-01-01&end=2015-04-15</td>
    </tr>
    <tr>
        <td>action=type</td>
        <td>Set the type of action you want the result of</td>
        <td>Options:
            <table>
                <tr>
                    <td>updated_signatures</td>
                    <td>Amount of signatures updated</td>
                </tr>
                <tr>
                    <td>scanned_signatures</td>
                    <td>Signatures scanned after adding</td>
                </tr>
                <tr>
                    <td>added_signatures</td>
                    <td>Signatures added/imported</td>
                </tr>
                <tr>
                    <td>added_systems</td>
                    <td>Systems added</td>
                </tr>
                <tr>
                    <td>edited_systems</td>
                    <td>Systems edited</td>
                </tr>
            </table>
        </td>
        <td>?action=scanned_signatures</td>
    </tr>
    <tr></tr>
    <tr></tr>
    </tbody>
</table>

</body>
</html>
