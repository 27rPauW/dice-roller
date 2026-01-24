import random


def roll_dice(num_dice: int, num_sides: int) -> list[int]:
    """Roll the specified number of dice with the given number of sides."""
    return [random.randint(1, num_sides) for _ in range(num_dice)]


def get_positive_int(prompt: str) -> int:
    """Get a positive integer from user input."""
    while True:
        try:
            value = int(input(prompt))
            if value < 1:
                print("Please enter a positive number.")
                continue
            return value
        except ValueError:
            print("Invalid input. Please enter a number.")


def main():
    print("=== Dice Roller ===\n")

    num_dice = get_positive_int("How many dice do you want to roll? ")
    num_sides = get_positive_int("How many sides per die? ")

    results = roll_dice(num_dice, num_sides)

    print(f"\nRolling {num_dice}d{num_sides}...\n")

    for i, result in enumerate(results, 1):
        print(f"  Die {i}: {result}")

    print(f"\n  Total: {sum(results)}")


if __name__ == "__main__":
    main()
