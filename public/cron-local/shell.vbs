'Starts a new windows shell process (command line).
Set WinScriptHost = CreateObject("WScript.Shell")

'Runs a command and sets the character set for the text string, "script.bat"
WinScriptHost.Run Chr(34) & "E:\wamp\www\cms4\public\cron-local\script.bat" & Chr(34), 0

'Tells the shell not to display the window.
Set WinScriptHost = Nothing