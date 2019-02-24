# Environment emulator

## Load data information

Map positions are expected to be included in the range {-180, 180}. If the range is different the application parameter
`src_to_rad_multiplication` can be used to modify the value as multiplier. For instance, if the source range are in
{-0.5, 0.5} then this parameter has to be 360.

```bash
# Load the data informations :
./console app:load:mesh filePath
```

If the range is unknown before processing the command can be used to get this information :

```bash
# --dry-run will skip database hydration
# --stat will print the x, the y and z range
./console app:load:mesh filePath --dry-run --stat
```

The command will flush the database before insertion. To append the new value without removing the old ones, then use
the `--append` option.

In case of memory limitation, the garbage collection can be tuned by modifying the `--gc-count` option.

## Test laser sensor

The `posx`, `posy` and `vehicleAltitude` are in meters unit.

The `vehicleAngle` is in degree.

```bash
./console app:sensor:laser posx posy vehicleAngle vehicleAltitude
```

The laser specificity can be tuned by modifying the laser parameter entry of the application. The existing parameters
are :

 * the detection range with `laser.meter_range`, defined in meter as float
 * the aperture angle with `laser.aperture_angle`, defined in degree
 
The `map_meter_width` is used for calculation of the resolved point coordinates. As the stored ones are in range
{-180, 180} the final metrics coordinates have to be recalculated. This fact allow to multiply the resolution by 
increasing the point density.

If the point density is not sufficient for output relevance, this density can be artificially increased by point
multiplication. To increase the density by this way, use the `vertices_doubling` parameter. The resulting vertices count
can be obtained by `n + (m²)` where `n` is the count of initial vertices and `m` the parameter value.
 
The `laser.relative_position` define if the resulting points coordinates have to be relatives to the given parameters
or have to be absolute on the map referential.

The command return the machine representation. To get human readable results, use the `-v` option.

# Protocol specification

### Laser sensor downstream

For the laser sensor, the data sent are : 
 * ASCII character SOH (hex x01) as data type
 * any number of _point_coordinates_ separated by the ASCII character GS (hex x1D)
 * ASCII character ETX (hex x03) as end of text
 
The _point_coordinates_ are :
 * ordinate position
 * ASCII character US as unit separator
 * absciss position
 * ASCII character US as unit delimiter
 * elevation position

```txt
This is the textual representation with two points (real output, separation character escaped by gitlab)
19.141-21.87615.613.282-25.78213.478
```

```hex
This is the hexadecimal representation with two points
  |  vertex1  X     |  |  vertex1 Y         |  | vertex1 Z |  |  vertex2 X      |  |  vertex2 Y         |  | vertex2 Z       |
01 31 39 2E 31 34 31 1F 2D 32 31 2E 38 37 36 1F 31 35 2E 36 1D 31 33 2E 32 38 32 1F 2D 32 35 2E 37 38 32 1F 31 33 2E 34 37 38 03
||                   ||                      ||             ||                   ||                      ||                   ||
01 (SOH)             ||                      ||             ||                   ||                      ||                   03 (ETX)
                     1F (US)                 1F             ||                   1F                      1F
                                                            1D (GS)
```
