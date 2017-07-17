# flameslater
by Bob Maple (bobm [at] burner [dot] com)

`flameslater` is a utility for Autodesk Flame for people who do lots of spot versioning.
It turns .csv files into Flame Text setups (.ttg files) using your own .ttg as a template.

It can also call `xclip` on Linux to place spot codes on the clipboard, allowing you to
easily rename your sequences in Flame using something like Klipper.

## Installing

There's no install per se, just download and place somewhere you have access to. For Linux
users, you may also want to grab xclip from https://github.com/astrand/xclip and building
that (or optionally installing from some repository) and placing the `xclip` binary in the
flameslater directory.

For a video tutorial on setting it all up: https://www.youtube.com/watch?v=GhuU0D26XvA

## Using

Make a template using the Text effect in Flame and create any dynamic fields (to be filled
in from the .csv) by surrounding them in % characters.  The only required field is %Spot Code%
which needs to source from a unique column in your .csv, as it is used for the filenames of
the .ttg setups that flameslater generates (and optionally copies to the clipboard for easy
paste-naming of clips in Flame.)

Optionally using the field %Date Now% will automatically be subsituted with the current date.

For a video tutorial with more info: https://www.youtube.com/watch?v=jR2UR1ddFUQ
